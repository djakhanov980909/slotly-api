<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $bookings = Booking::query()
            ->with(['service', 'specialist', 'client'])
            ->when($user->role === Role::Client, fn ($q) => $q->where('client_id', $user->id))
            ->when($user->role === Role::Specialist, fn ($q) => $q->where('specialist_id', $user->id))
            ->orderByDesc('starts_at')
            ->paginate(15);

        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request, BookingService $bookings): BookingResource
    {
        $start = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $request->validated('date').' '.$request->validated('time'),
            config('slotly.timezone'),
        );

        $booking = $bookings->create(
            $request->user(),
            User::findOrFail($request->validated('specialist_id')),
            Service::findOrFail($request->validated('service_id')),
            $start,
        );

        return new BookingResource($booking->load(['service', 'specialist', 'client']));
    }

    public function cancel(Booking $booking): BookingResource
    {
        Gate::authorize('cancel', $booking);

        $booking->update(['status' => BookingStatus::Cancelled]);

        return new BookingResource($booking->load(['service', 'specialist', 'client']));
    }
}
