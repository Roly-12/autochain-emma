<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class BrevoApiTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $endpoint = 'https://api.brevo.com/v3/smtp/email',
        private readonly int $timeout = 15,
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'brevo+api';
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->apiKey === '') {
            throw new TransportException('BREVO_API_KEY est manquante.');
        }

        try {
            $email = MessageConverter::toEmail($message->getOriginalMessage());
        } catch (\Throwable $exception) {
            throw new TransportException(
                'Impossible de convertir le message pour Brevo.',
                previous: $exception
            );
        }

        $sender = $email->getFrom()[0] ?? null;
        if (! $sender instanceof Address) {
            throw new TransportException('L’adresse MAIL_FROM_ADDRESS est manquante.');
        }

        $payload = [
            'sender' => $this->address($sender),
            'to' => array_map($this->address(...), $message->getEnvelope()->getRecipients()),
            'subject' => $email->getSubject() ?: config('app.name'),
        ];

        if ($email->getHtmlBody() !== null) {
            $payload['htmlContent'] = $email->getHtmlBody();
        }

        if ($email->getTextBody() !== null) {
            $payload['textContent'] = $email->getTextBody();
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
            ])->timeout($this->timeout)->post($this->endpoint, $payload);
        } catch (\Throwable $exception) {
            throw new TransportException(
                'Connexion à l’API email Brevo impossible.',
                previous: $exception
            );
        }

        if ($response->failed()) {
            throw new TransportException(
                'Brevo a refusé l’email (HTTP '.$response->status().').'
            );
        }
    }

    /**
     * @return array{email: string, name?: string}
     */
    private function address(Address $address): array
    {
        return array_filter([
            'email' => $address->getAddress(),
            'name' => $address->getName(),
        ]);
    }
}
