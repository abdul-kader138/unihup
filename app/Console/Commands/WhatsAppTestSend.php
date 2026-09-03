<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppException;
use Illuminate\Console\Command;

class WhatsAppTestSend extends Command
{
    protected $signature = 'whatsapp:test-send
        {to : Recipient phone number in E.164, e.g. +393331234567}
        {--body=Hello from UniHup 👋 : Text to send (ignored when --template is given)}
        {--template= : Send this approved template instead of free-form text}
        {--lang=en : Template language code (with --template)}';

    protected $description = 'Send a one-off WhatsApp message to verify Cloud API credentials';

    public function handle(): int
    {
        $client = WhatsAppClient::fromConfig();

        if (! $client->configured()) {
            $this->error('WhatsApp is not configured — set WHATSAPP_PHONE_NUMBER_ID and WHATSAPP_ACCESS_TOKEN in .env.');

            return self::FAILURE;
        }

        $to = (string) $this->argument('to');
        $template = $this->option('template');

        try {
            $id = $template
                ? $client->sendTemplate($to, (string) $template, (string) $this->option('lang'))
                : $client->sendText($to, (string) $this->option('body'));
        } catch (WhatsAppException $e) {
            $this->error($e->getMessage());
            if ($e->context !== []) {
                $this->line(json_encode($e->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            return self::FAILURE;
        }

        $this->info("Sent. Message id: {$id}");

        return self::SUCCESS;
    }
}
