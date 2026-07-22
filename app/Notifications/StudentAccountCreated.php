<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAccountCreated extends Notification
{
    use Queueable;

    public function __construct(
        private readonly School $school,
        private readonly string $password,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre compte {$this->school->name}")
            ->replyTo($this->school->email, $this->school->name)
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre compte client a été créé automatiquement à la suite de votre inscription auprès de {$this->school->name}.")
            ->line("Adresse e-mail : {$notifiable->email}")
            ->line("Mot de passe temporaire : {$this->password}")
            ->line('Conservez ces identifiants. Vous pourrez modifier votre mot de passe depuis votre espace client.')
            ->action('Accéder à mon espace élève', route('students.login', $this->school))
            ->line('À bientôt !');
    }
}
