<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\TimeOff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SlotService
{
    /**
     * @return Collection<int, CarbonImmutable> время начала свободных слотов
     */
    public function availableSlots(User $specialist, Service $service, CarbonInterface $date): Collection
    {
        $timezone = config('slotly.timezone');
        $step = config('slotly.slot_step_minutes');
        $duration = $service->duration_minutes;

        $dayStart = CarbonImmutable::parse($date->format('Y-m-d'), $timezone)->startOfDay();
        $dayEnd = $dayStart->addDay();

        $windows = $specialist->workingHours()
            ->where('weekday', $dayStart->isoWeekday())
            ->orderBy('start_time')
            ->get();

        if ($windows->isEmpty()) {
            return collect();
        }

        $busy = $this->busyIntervals($specialist, $dayStart, $dayEnd);
        $now = now()->toImmutable();
        $slots = collect();

        foreach ($windows as $window) {
            $cursor = $dayStart->setTimeFromTimeString($window->start_time);
            $windowEnd = $dayStart->setTimeFromTimeString($window->end_time);

            while ($cursor->addMinutes($duration) <= $windowEnd) {
                $slotEnd = $cursor->addMinutes($duration);

                if ($cursor > $now && ! $this->overlapsAny($cursor, $slotEnd, $busy)) {
                    $slots->push($cursor);
                }

                $cursor = $cursor->addMinutes($step);
            }
        }

        return $slots->values();
    }

    /**
     * Занятые интервалы: неотменённые записи и отпуска.
     *
     * @return Collection<int, array{0: Carbon, 1: Carbon}>
     */
    private function busyIntervals(User $specialist, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        // В базе время хранится в UTC, поэтому границы запроса тоже переводим в UTC.
        $from = $from->utc();
        $to = $to->utc();

        $bookings = $specialist->specialistBookings()
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->get(['starts_at', 'ends_at'])
            ->map(fn (Booking $b) => [$b->starts_at, $b->ends_at]);

        $timeOffs = $specialist->timeOffs()
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->get(['starts_at', 'ends_at'])
            ->map(fn (TimeOff $t) => [$t->starts_at, $t->ends_at]);

        return $bookings->concat($timeOffs)->values();
    }

    /** @param Collection<int, array{0: Carbon, 1: Carbon}> $busy */
    private function overlapsAny(CarbonImmutable $start, CarbonImmutable $end, Collection $busy): bool
    {
        // Интервалы полуоткрытые [начало, конец): запись до 10:00 не мешает записи с 10:00.
        return $busy->contains(fn (array $interval) => $start < $interval[1] && $end > $interval[0]);
    }
}
