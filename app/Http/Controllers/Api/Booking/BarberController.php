<?php

namespace App\Http\Controllers\Api\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\BarberStoreRequest;
use App\Http\Requests\Booking\BarberUpdateRequest;
use App\Http\Resources\Booking\BarberResource;
use Illuminate\Http\Request;
use App\Models\Barber;
use Illuminate\Support\Facades\Log;

class BarberController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Barber::class);

        $barbers = Barber::all();

        return BarberResource::collection($barbers);
    }

    public function store(BarberStoreRequest $request)
    {
        $this->authorize('create', Barber::class);

        try {
            $barber = Barber::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Barber created successfully',
                'data' => new BarberResource($barber)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating barber: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create barber'
            ], 500);
        }
    }

    public function show(Barber $barber)
    {
        $this->authorize('view', $barber);

        return new BarberResource($barber);
    }

    public function update(BarberUpdateRequest $request, Barber $barber)
    {
        $this->authorize('update', $barber);

        try {
            $barber->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Barber updated successfully',
                'data' => new BarberResource($barber)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating barber: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update barber'
            ], 500);
        }
    }

    public function destroy(Barber $barber)
    {
        $this->authorize('delete', $barber);

        try {
            // contoh jika barber punya relasi service_barbers
            if ($barber->serviceBarbers()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete barber with existing service assignments'
                ], 422);
            }

            $barber->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Barber deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting barber: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete barber'
            ], 500);
        }
    }
}
