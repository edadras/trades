<?php

namespace Tests\Feature;

use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessagingChannelsTest extends TestCase
{
    public function test_kavenegar_driver_posts_the_message_to_the_gateway(): void
    {
        config(['platform.messaging_channels.sms' => 'kavenegar', 'platform.messaging_channels.kavenegar' => ['api_key' => 'KEY123', 'sender' => '10004346']]);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 200]])]);

        app(SmsChannel::class)->deliver('09121234567', 'Meeting at 10:00');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.kavenegar.com/v1/KEY123/sms/send.json'
            && $r['receptor'] === '09121234567' && $r['message'] === 'Meeting at 10:00' && $r['sender'] === '10004346');
    }

    public function test_whatsapp_cloud_driver_sends_a_text_message(): void
    {
        config(['platform.messaging_channels.whatsapp' => 'cloud_api', 'platform.messaging_channels.whatsapp_cloud' => ['token' => 'TOKEN', 'phone_number_id' => '555', 'version' => 'v21.0']]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        app(WhatsAppChannel::class)->deliver('+989121234567', 'Your case was updated');

        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://graph.facebook.com/v21.0/555/messages')
            && $r->hasHeader('Authorization', 'Bearer TOKEN') && $r['type'] === 'text' && $r['text']['body'] === 'Your case was updated');
    }

    public function test_gateway_errors_are_raised_so_the_queue_retries(): void
    {
        config(['platform.messaging_channels.sms' => 'webhook', 'platform.messaging_channels.sms_webhook' => ['url' => 'https://sms.example.test/send', 'token' => 't']]);
        Http::fake(['sms.example.test/*' => Http::response('down', 503)]);

        $this->expectException(RequestException::class);
        app(SmsChannel::class)->deliver('09121234567', 'Hello');
    }
}
