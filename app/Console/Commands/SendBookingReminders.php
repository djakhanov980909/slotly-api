<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-booking-reminders')]
#[Description('Command description')]
class SendBookingReminders extends Command
{
    public function handle(): int
    {
        $sent = 0;

        Booking::query()
            ->with(['client', 'service', 'specialist'])
            ->where('status', BookingStatus::Confirmed->value)
            ->whereNull('reminder_sent_at')
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addDay())
            ->where('created_at', '<=', now()->subMinutes(60))
            ->chunkById(100, function ($bookings) use (&$sent) {
                foreach ($bookings as $booking) {
                    // Сначала «занимаем» запись одним атомарным UPDATE, потом шлём письмо
                    $claimed = Booking::whereKey($booking->id)
                        ->whereNull('reminder_sent_at')
                        ->update(['reminder_sent_at' => now()]);

                    if ($claimed) {
                        $booking->client->notify(new BookingReminder($booking));
                        $sent++;
                    }
                }
            });

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
