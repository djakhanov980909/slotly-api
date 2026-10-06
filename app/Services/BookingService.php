<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingCreated;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    // Код ошибки PostgreSQL: нарушение exclusion constraint
    private const EXCLUSION_VIOLATION = '23P01';

    public function __construct(private SlotService $slots) {}

    public function create(User $client, User $specialist, Service $service, CarbonImmutable $start): Booking
    {
        if (! $specialist->services()->whereKey($service->id)->exists()) {
            throw ValidationException::withMessages([
                'service_id' => ['Этот специалист не оказывает выбранную услугу.'],
            ]);
        }

        // Слой 1: время должно быть в списке свободных слотов
        $available = $this->slots->availableSlots($specialist, $service, $start)
            ->contains(fn (CarbonImmutable $slot) => $slot->equalTo($start));

        if (! $available) {
            throw new SlotUnavailableException;
        }

        // Слой 2: база данных не даст создать пересекающуюся запись
        try {
            $booking = DB::transaction(fn () => Booking::create([
                'client_id' => $client->id,
                'specialist_id' => $specialist->id,
                'service_id' => $service->id,
                'starts_at' => $start->utc(),
                'ends_at' => $start->addMinutes($service->duration_minutes)->utc(),
                'status' => BookingStatus::Confirmed,
                'price' => $service->price,
            ]));
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === self::EXCLUSION_VIOLATION) {
                throw new SlotUnavailableException;
            }

            throw $e;
        }

        BookingCreated::dispatch($booking);

        return $booking;
    }
}
