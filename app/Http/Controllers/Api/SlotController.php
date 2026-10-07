<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\SlotsRequest;
use App\Models\Service;
use App\Models\User;
use App\Services\SlotService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @unauthenticated
     */
    public function __invoke(SlotsRequest $request, User $specialist, SlotService $slots): JsonResponse
    {
        abort_unless($specialist->role === Role::Specialist, 404);

        $service = Service::where('is_active', true)->findOrFail((int) $request->validated('service_id'));

        abort_unless($specialist->services()->whereKey($service->id)->exists(), 404);

        $date = CarbonImmutable::parse($request->validated('date'));

        $times = $slots->availableSlots($specialist, $service, $date)
            ->map(fn ($slot) => $slot->format('H:i'))
            ->values();

        return response()->json([
            'data' => [
                'date' => $date->toDateString(),
                'timezone' => config('slotly.timezone'),
                'duration_minutes' => $service->duration_minutes,
                'slots' => $times,
            ],
        ]);
    }
}
