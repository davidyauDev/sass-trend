<?php

namespace App\Notifications\Users;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $invitationUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitación para acceder a la plataforma')
            ->greeting('Hola, '.$notifiable->displayFirstName())
            ->line('Se creó un usuario interno para ti en la plataforma.')
            ->line('Usa el siguiente enlace para definir tu contraseña y completar el acceso inicial.')
            ->action('Completar invitación', $this->invitationUrl)
            ->line('Si no esperabas esta invitación, puedes ignorar este correo.');
    }
}
