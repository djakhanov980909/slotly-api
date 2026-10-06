<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Notifications\BookingCancelledNotice;

class SendBookingCancellation
{
    public function handle(BookingCancelled $event): void
    {
        $booking = $event->booking;

        collect([$booking->client, $booking->specialist])
            ->reject(fn ($user) => $user->id === $event->cancelledById)
            ->each(fn ($user) => $user->notify(new BookingCancelledNotice($booking)));
    }
}
