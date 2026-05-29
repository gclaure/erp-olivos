<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeTenantNotification extends Notification
{
    use Queueable;

    private Company $company;
    private string $password;

    /**
     * Create a new notification instance.
     */
    public function __construct(Company $company, string $password)
    {
        $this->company = $company;
        $this->password = $password;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Bienvenido a ' . config('app.name') . '!')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tu empresa **' . $this->company->name . '** ha sido configurada exitosamente.')
            ->line('Para empezar a usar el sistema, puedes iniciar sesión con las siguientes credenciales temporales:')
            ->line('**Usuario (Correo):** ' . $notifiable->email)
            ->line('**Contraseña temporal:** ' . $this->password)
            ->action('Iniciar Sesión', url('/login'))
            ->line('Por razones de seguridad, te recomendamos cambiar tu contraseña una vez hayas ingresado.')
            ->salutation('Atentamente, el equipo de ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
