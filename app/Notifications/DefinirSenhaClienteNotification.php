<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DefinirSenhaClienteNotification extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Monta a URL usando aquela rota que criamos no web.php
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Bem-vindo! Defina sua senha de acesso')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Sua conta no portal da nossa Seguradora foi criada com sucesso.')
            ->line('Para acessar suas apólices e cotações, por favor, clique no botão abaixo para criar sua senha de acesso seguro:')
            ->action('Definir Minha Senha', $url)
            ->line('Se você não solicitou este cadastro, pode ignorar este e-mail.')
            ->salutation('Um abraço, Equipe de Atendimento');
    }
}