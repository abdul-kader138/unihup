<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a media file a customer sent us (stored off the public disk by
 * App\Jobs\ProcessInboundWhatsAppJob) to a staff member viewing the inbox.
 * Gated by the same permission as the inbox page itself.
 */
class WhatsAppMediaController extends Controller
{
    public function __invoke(WhatsAppMessage $message): StreamedResponse
    {
        abort_unless((bool) auth()->user()?->can('page_WhatsAppInbox'), 403);
        abort_if($message->media_path === null, 404);

        $disk = Storage::disk((string) config('services.whatsapp.media_disk'));
        abort_unless($disk->exists($message->media_path), 404);

        return $disk->response($message->media_path, null, [
            'Content-Type' => $message->media_mime ?: 'application/octet-stream',
        ]);
    }
}
