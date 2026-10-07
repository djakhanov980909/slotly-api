<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    /**
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(
            Service::query()->where('is_active', true)->orderBy('name')->paginate(15)
        );
    }

    /**
     * @unauthenticated
     */
    public function show(Service $service): ServiceResource
    {
        abort_unless($service->is_active, 404);

        return new ServiceResource($service->load('specialists'));
    }

    public function store(StoreServiceRequest $request): ServiceResource
    {
        return new ServiceResource(Service::create($request->validated())->refresh());
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $service->update($request->validated());

        return new ServiceResource($service);
    }
}
