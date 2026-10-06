<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Notifications\BookingConfirmed;

class SendBookingConfirmation
{
    public function handle(BookingCreated $event): void
    {
        $event->booking->client->notify(new BookingConfirmed($event->booking));
    }
}
