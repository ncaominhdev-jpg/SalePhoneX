<?php

// app/Notifications/ResetPasswordNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url(config('app.url').'/reset-password?token='.$this->token.'&email='.urlencode($notifiable->email));

        return (new MailMessage)
            ->subject('Reset Mật Khẩu')
            ->line('Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu reset mật khẩu cho tài khoản của bạn.')
            ->action('Reset Mật Khẩu', $url)
            ->line('Link này sẽ hết hạn sau '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' phút.')
            ->line('Nếu bạn không yêu cầu reset mật khẩu, không cần thực hiện thêm hành động nào.');
    }
}