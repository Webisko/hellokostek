<?php

namespace App\Support;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

class NativePhpMailTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $original = $message->getMessage();

        if ($original instanceof Email) {
            $to = implode(', ', array_map(fn ($a) => $a->toString(), $original->getTo()));
            $subject = $original->getSubject() ?? '';
            $from = implode(', ', array_map(fn ($a) => $a->toString(), $original->getFrom()));
            $replyTo = implode(', ', array_map(fn ($a) => $a->toString(), $original->getReplyTo()));

            $headers = [];
            if (!empty($from)) {
                $headers[] = "From: {$from}";
            }
            if (!empty($replyTo)) {
                $headers[] = "Reply-To: {$replyTo}";
            }
            $headers[] = 'MIME-Version: 1.0';

            $html = $original->getHtmlBody();
            $text = $original->getTextBody();

            if (!empty($html)) {
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $body = is_resource($html) ? stream_get_contents($html) : (string) $html;
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
                $body = is_resource($text) ? stream_get_contents($text) : (string) ($text ?? '');
            }

            $headerString = implode("\r\n", $headers);

            // Send via PHP native mail()
            $result = @mail($to, $subject, $body, $headerString);

            if (!$result) {
                $lastError = error_get_last()['message'] ?? 'Unknown error';
                throw new \RuntimeException("Native mail() delivery failed: {$lastError}");
            }
        } else {
            $recipient = $message->getEnvelope()->getRecipients()[0]?->getAddress();
            if ($recipient) {
                @mail($recipient, 'Hello Kostek Notification', $original->toString());
            }
        }
    }

    public function __toString(): string
    {
        return 'native-mail://';
    }
}
