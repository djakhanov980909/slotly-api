<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotice extends Notification implements ShouldQueueAfterCommit
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
        $booking = $this->booking->loadMissing('service');
        $time = $booking->starts_at->setTimezone(config('slotly.timezone'))->format('d.m.Y H:i');

        return (new MailMessage)
            ->subject('Запись отменена')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Запись на «{$booking->service->name}» ({$time}) была отменена.")
            ->salutation('С уважением, Slotly');
    }
}
