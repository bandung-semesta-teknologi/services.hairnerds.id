<?php

namespace App\Http\Controllers\Api\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ServiceBarberStoreRequest;
use App\Http\Requests\booking\ServiceBarberUpdateRequest;
use App\Http\Resources\Booking\ServiceBarberResource;
use App\Models\ServiceBarber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceBarberController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ServiceBarber::class);

        $serviceBarbers = ServiceBarber::with(['service', 'barber'])->get();

        return ServiceBarberResource::collection($serviceBarbers);
    }

    public function store(ServiceBarberStoreRequest $request)
    {
        $this->authorize('create', ServiceBarber::class);

        try {
            $serviceBarber = ServiceBarber::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Service barber created successfully',
                'data' => new ServiceBarberResource($serviceBarber)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating service barber: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service barber'
            ], 500);
        }
    }

    public function show(ServiceBarber $serviceBarber)
    {
        $this->authorize('view', $serviceBarber);

        $serviceBarber->load(['service', 'barber']);

        return new ServiceBarberResource($serviceBarber);
    }

    public function update(ServiceBarberUpdateRequest $request, ServiceBarber $serviceBarber)
    {
        $this->authorize('update', $serviceBarber);

        try {
            $serviceBarber->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Service barber updated successfully',
                'data' => new ServiceBarberResource($serviceBarber)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating service barber: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update service barber'
            ], 500);
        }
    }

    public function destroy(ServiceBarber $serviceBarber)
    {
        $this->authorize('delete', $serviceBarber);

        try {
            $serviceBarber->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Service barber deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting service barber: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete service barber'
            ], 500);
        }
    }
}
