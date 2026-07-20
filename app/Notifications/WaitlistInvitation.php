<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WaitlistInvitation extends Notification
{
    use Queueable;

    public function __construct(public readonly Enrollment $enrollment, public readonly string $token) {}
    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $enrollment = $this->enrollment->loadMissing(['school', 'course']);
        $url = URL::temporarySignedRoute('waitlist.accept', $enrollment->waitlist_invitation_expires_at, [
            'enrollment' => $enrollment->id,
            'token' => $this->token,
        ]);

        return (new MailMessage)
            ->subject("Une place est disponible — {$enrollment->course->title}")
            ->replyTo($enrollment->school->email, $enrollment->school->name)
            ->greeting("Bonjour {$enrollment->first_name},")
            ->line("Une place correspondant à votre rôle {$enrollment->dance_role} est maintenant disponible pour le cours {$enrollment->course->title}.")
            ->line('L’équilibre entre Leads et Follows permet de préserver la qualité du cours. La priorité vous est accordée car vous êtes la première personne éligible sur la liste d’attente.')
            ->action('Valider mon inscription', $url)
            ->line("Ce lien est personnel, utilisable une seule fois et valable pendant {$enrollment->course->waitlist_invitation_hours} heure(s).")
            ->salutation("L’équipe de {$enrollment->school->name}");
    }
}
