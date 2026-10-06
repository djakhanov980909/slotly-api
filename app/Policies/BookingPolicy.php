<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function cancel(User $user, Booking $booking): bool
    {
        $isParticipant = in_array($user->id, [$booking->client_id, $booking->specialist_id], true);

        return ($isParticipant || $user->role === Role::Admin)
            && in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)
            && $booking->starts_at->isFuture();
    }
}
