<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = urlencode((string) $notifiable->email);
        $baseUrl = rtrim((string) config('services.storefront.url', config('app.url')), '/');
        $resetUrl = $baseUrl . '/reset-password?token=' . urlencode($this->token) . '&email=' . $email;

        return (new MailMessage)
            ->subject('Reset hasła do Twojego konta')
            ->greeting('Witaj!')
            ->line('Otrzymalismy prosbe o zresetowanie hasla do Twojego konta.')
            ->action('Ustaw nowe haslo', $resetUrl)
            ->line('Jesli to nie Ty inicjowales ten proces, zignoruj te wiadomosc.');
    }
}