<?php

namespace App\Http\Controllers\Api\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CatalogCategoryStoreRequest;
use App\Http\Requests\Booking\CatalogCategoryUpdateRequest;
use App\Http\Resources\Booking\CatalogCategoryResource;
use App\Models\CatalogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CatalogCategoryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CatalogCategory::class);

        $categories = CatalogCategory::all();

        return CatalogCategoryResource::collection($categories);
    }

    public function store(CatalogCategoryStoreRequest $request)
    {
        $this->authorize('create', CatalogCategory::class);

        try {
            $category = CatalogCategory::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Catalog category created successfully',
                'data' => new CatalogCategoryResource($category)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating catalog category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create catalog category'
            ], 500);
        }
    }

    public function show(CatalogCategory $catalogCategory)
    {
        $this->authorize('view', $catalogCategory);

        return new CatalogCategoryResource($catalogCategory);
    }

    public function update(CatalogCategoryUpdateRequest $request, CatalogCategory $catalogCategory)
    {
        $this->authorize('update', $catalogCategory);

        try {
            $catalogCategory->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Catalog category updated successfully',
                'data' => new CatalogCategoryResource($catalogCategory)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating catalog category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update catalog category'
            ], 500);
        }
    }

    public function destroy(CatalogCategory $catalogCategory)
    {
        $this->authorize('delete', $catalogCategory);

        try {
            if ($catalogCategory->catalogs()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with existing catalogs'
                ], 422);
            }

            $catalogCategory->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Catalog category deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting catalog category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete catalog category'
            ], 500);
        }
    }
}
