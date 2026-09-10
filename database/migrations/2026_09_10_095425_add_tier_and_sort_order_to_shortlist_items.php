<?php

use App\Models\ShortlistItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-program planning metadata on the student's list:
 *  - tier: reach / target / safety (see ShortlistItem::TIERS), seeded from
 *    the eligibility read but overridable by the student.
 *  - sort_order: manual drag ranking on My Applications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlist_items', function (Blueprint $table) {
            $table->string('tier')->nullable()->after('status');
            $table->unsignedInteger('sort_order')->default(0)->after('tier');
        });

        ShortlistItem::query()->with(['degreeProgram.university', 'user'])
            ->orderBy('id')
            ->get()
            ->groupBy('user_id')
            ->each(function ($items) {
                $order = 0;
                foreach ($items as $item) {
                    $item->forceFill([
                        'tier' => $item->suggestedTier(),
                        'sort_order' => $order++,
                    ])->save();
                }
            });
    }

    public function down(): void
    {
        Schema::table('shortlist_items', function (Blueprint $table) {
            $table->dropColumn(['tier', 'sort_order']);
        });
    }
};
