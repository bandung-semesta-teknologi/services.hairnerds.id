<?php

namespace App\Http\Controllers\Api\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ServiceCategoryStoreRequest;
use App\Http\Requests\Booking\ServiceCategoryUpdateRequest;
use App\Http\Resources\Booking\ServiceCategoryResource;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ServiceCategory::class);

        $categories = ServiceCategory::all();

        return ServiceCategoryResource::collection($categories);
    }

    public function store(ServiceCategoryStoreRequest $request)
    {
        $this->authorize('create', ServiceCategory::class);

        try {
            $category = ServiceCategory::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Service category created successfully',
                'data' => new ServiceCategoryResource($category)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating service category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service category'
            ], 500);
        }
    }

    public function show(ServiceCategory $serviceCategory)
    {
        $this->authorize('view', $serviceCategory);

        return new ServiceCategoryResource($serviceCategory);
    }

    public function update(ServiceCategoryUpdateRequest $request, ServiceCategory $serviceCategory)
    {
        $this->authorize('update', $serviceCategory);

        try {
            $serviceCategory->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Service category updated successfully',
                'data' => new ServiceCategoryResource($serviceCategory)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating service category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update service category'
            ], 500);
        }
    }

    public function destroy(ServiceCategory $serviceCategory)
    {
        $this->authorize('delete', $serviceCategory);

        try {
            if ($serviceCategory->services()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with existing services'
                ], 422);
            }

            $serviceCategory->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Service category deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting service category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete service category'
            ], 500);
        }
    }
}
