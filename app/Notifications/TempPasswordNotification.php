<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TempPasswordNotification extends Notification
{
    use Queueable;

    protected string $tempPassword;

    public function __construct(string $tempPassword)
    {
        $this->tempPassword = $tempPassword;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre mot de passe temporaire - Tontine')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Une demande de réinitialisation de mot de passe a été effectuée pour votre compte.')
            ->line('Voici votre mot de passe temporaire :')
            ->line('**' . $this->tempPassword . '**')
            ->line('Pour des raisons de sécurité, vous serez invité à définir un nouveau mot de passe dès votre prochaine connexion.')
            ->action('Se connecter', url('/'))
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, veuillez contacter votre administrateur.');
    }
}
