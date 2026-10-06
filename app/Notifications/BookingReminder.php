<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing(['service', 'specialist']);
        $time = $booking->starts_at->setTimezone(config('slotly.timezone'))->format('d.m.Y H:i');

        return (new MailMessage)
            ->subject('Напоминание о записи')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Напоминаем: скоро у вас запись на «{$booking->service->name}».")
            ->line("Специалист: {$booking->specialist->name}")
            ->line("Время: {$time}")
            ->salutation('С уважением, Slotly');
    }
}
