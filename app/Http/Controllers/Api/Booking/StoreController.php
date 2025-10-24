<?php

namespace App\Http\Controllers\Api\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreStoreRequest;
use App\Http\Requests\Booking\StoreUpdateRequest;
use App\Http\Resources\Booking\StoreResource;
use Illuminate\Http\Request;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Store::class);

        $store = Store::all();

        return StoreResource::collection($store);
    }

    public function store(StoreStoreRequest $request)
    {
        $this->authorize('create', Store::class);

        try{
            $store = Store::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Store created successfully',
                'data' => new StoreResource($store)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating store: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create store'
            ], 500);
        }
    }

    public function show(Store $store)
    {
        $this->authorize('view', $store);

        return new StoreResource($store);
    }

    public function update(StoreUpdateRequest $request, Store $store)
    {
        $this->authorize('update', $store);

        try {
            $store->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Store updated successfully',
                'data' => new StoreResource($store)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating store: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update store'
            ], 500);
        }
    }

    public function destroy(Store $store)
    {
        $this->authorize('delete', $store);

        try {
            if ($store->courses()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete store with existing courses'
                ], 422);
            }

            $store->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Store deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting store: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete store'
            ], 500);
        }
    }
}
