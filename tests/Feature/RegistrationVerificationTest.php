<?php

namespace Tests\Feature;

use App\Filament\Auth\Register;
use App\Models\User;
use App\Notifications\Auth\VerifyEmail;
use Database\Seeders\ShieldSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_one_verification_with_the_listener(): void
    {
        $this->registerAndAssertVerification();
    }

    public function test_registration_sends_verification_without_the_listener(): void
    {
        Event::forget(Registered::class);

        $this->registerAndAssertVerification();
    }

    public function test_registration_queues_one_mail_job_and_worker_processes_it(): void
    {
        $this->seed(ShieldSeeder::class);
        config(['queue.default' => 'redis', 'mail.default' => 'array']);
        Queue::fake();
        Log::spy();

        $this->submitRegistration();

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        Queue::assertPushed(SendQueuedNotifications::class, 1);
        Queue::assertPushed(SendQueuedNotifications::class, function ($job) use ($user): bool {
            if (! $job->notification instanceof VerifyEmail || ! $job->notifiables->first()->is($user)) {
                return false;
            }

            // Exercise the real mail channel without contacting SMTP.
            $job = unserialize(serialize($job));
            $job->handle(app(ChannelManager::class));

            return $job->channels === ['mail'];
        });

        Log::shouldHaveReceived('info')->with('verification.transport_completed', \Mockery::on(
            fn (array $context): bool => $context['user_id'] === $user->id && $context['mailer'] === 'array'
        ))->once();
    }

    private function registerAndAssertVerification(): void
    {
        $this->seed(ShieldSeeder::class);
        Notification::fake();
        $this->submitRegistration();

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);

        // A later resend remains available on a freshly loaded user.
        $user->sendEmailVerificationNotification();
        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    }

    private function submitRegistration(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'email' => 'ada@example.com',
                'password' => 'Password123',
                'passwordConfirmation' => 'Password123',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect('/find-universities');
    }
}
