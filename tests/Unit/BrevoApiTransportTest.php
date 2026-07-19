<?php

namespace Tests\Unit;

use App\Mail\Transport\BrevoApiTransport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class BrevoApiTransportTest extends TestCase
{
    public function test_it_sends_email_through_brevo_https_api(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => 'test-id'], 201),
        ]);

        $email = (new Email)
            ->from('noreply@example.com')
            ->to('buyer@example.com')
            ->subject('Code AutoChain')
            ->html('<p>123456</p>');

        (new BrevoApiTransport('test-key'))->send($email);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', 'test-key')
            && $request['to'][0]['email'] === 'buyer@example.com'
            && $request['subject'] === 'Code AutoChain');
    }
}
