<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin,instructor')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $categories = Category::paginate($request->per_page ?? 5);

        return CategoryResource::collection($categories);
    }

    public function store(CategoryStoreRequest $request)
    {
        try {
            $category = Category::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => new CategoryResource($category)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create category'
            ], 500);
        }
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(CategoryUpdateRequest $request, Category $category)
    {
        try {
            $category->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => new CategoryResource($category)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update category'
            ], 500);
        }
    }

    public function destroy(Category $category)
    {
        try {
            if ($category->courses()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with existing courses'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete category'
            ], 500);
        }
    }

    private function isAdminOrInstructor(Request $request): bool
    {
        $user = $request->user();
        return $user && in_array($user->role, ['admin', 'instructor']);
    }
}
