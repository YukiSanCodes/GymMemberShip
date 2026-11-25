<?php

namespace App\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
    public function __construct(private string $key)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'brevo';
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->key) === '') {
            throw new TransportException('BREVO_API_KEY is missing.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $address = fn (Address $address): array => array_filter([
            'email' => $address->getAddress(),
            'name' => $address->getName(),
        ], fn (string $value): bool => $value !== '');

        $payload = [
            'sender' => $address($email->getFrom()[0]),
            'to' => array_map($address, $email->getTo()),
            'subject' => $email->getSubject(),
        ];

        foreach (['cc' => $email->getCc(), 'bcc' => $email->getBcc()] as $field => $recipients) {
            if ($recipients !== []) {
                $payload[$field] = array_map($address, $recipients);
            }
        }
        if ($email->getReplyTo() !== []) {
            $payload['replyTo'] = $address($email->getReplyTo()[0]);
        }
        if ($email->getHtmlBody() !== null) {
            $payload['htmlContent'] = $email->getHtmlBody();
        }
        if ($email->getTextBody() !== null) {
            $payload['textContent'] = $email->getTextBody();
        }
        foreach ($email->getAttachments() as $attachment) {
            $payload['attachment'][] = [
                'name' => $attachment->getFilename(),
                'content' => base64_encode($attachment->bodyToString() === '' ? '' : $attachment->getBody()),
            ];
        }

        try {
            $response = Http::withHeaders(['api-key' => $this->key])
                ->acceptJson()->connectTimeout(5)->timeout(20)
                ->post('https://api.brevo.com/v3/smtp/email', $payload);
        } catch (ConnectionException) {
            throw new TransportException('Could not connect to Brevo. The queued email can be retried.');
        }

        if (! $response->successful()) {
            throw new TransportException('Brevo rejected the email (HTTP '.$response->status().'). Check the API key, verified sender, and Brevo sending limits.');
        }

        if (is_string($response->json('messageId'))) {
            $message->setMessageId($response->json('messageId'));
        }
    }
}
