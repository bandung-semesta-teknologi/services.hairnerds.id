<?php

namespace App\Http\Controllers\Api\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ServiceStoreRequest;
use App\Http\Requests\Booking\ServiceUpdateRequest;
use App\Http\Resources\Booking\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::all();

        return ServiceResource::collection($services);
    }

    public function store(ServiceStoreRequest $request)
    {
        $this->authorize('create', Service::class);

        try {
            $service = Service::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Service created successfully',
                'data' => new ServiceResource($service)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating service: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service'
            ], 500);
        }
    }

    public function show(Service $service)
    {
        $this->authorize('view', $service);

        return new ServiceResource($service);
    }

    public function update(ServiceUpdateRequest $request, Service $service)
    {
        $this->authorize('update', $service);

        try {
            $service->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Service updated successfully',
                'data' => new ServiceResource($service)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating service: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update service'
            ], 500);
        }
    }

    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);

        try {
            if ($service->barbers()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete service with assigned barbers'
                ], 422);
            }

            $service->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Service deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting service: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete service'
            ], 500);
        }
    }
}
