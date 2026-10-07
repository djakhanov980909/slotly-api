<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing(['service', 'specialist']);
        $time = $booking->starts_at->setTimezone(config('slotly.timezone'))->format('d.m.Y H:i');

        return (new MailMessage)
            ->subject('Запись подтверждена')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вы записаны на услугу «{$booking->service->name}».")
            ->line("Специалист: {$booking->specialist->name}")
            ->line("Время: {$time}")
            ->line('Стоимость: '.number_format($booking->price, 0, '.', ' '))
            ->salutation('С уважением, Slotly');
    }
}
