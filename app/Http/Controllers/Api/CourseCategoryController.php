<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseCategoryStoreRequest;
use App\Http\Requests\CourseCategoryUpdateRequest;
use App\Http\Resources\CourseCategoryResource;
use App\Models\CourseCategory;
use Illuminate\Http\Request;

class CourseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = CourseCategory::paginate($request->per_page ?? 5);

        return CourseCategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseCategoryStoreRequest $request)
    {
        $category = CourseCategory::create($request->validated());

        return new CourseCategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(CourseCategory $courseCategory)
    {
        return new CourseCategoryResource($courseCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CourseCategoryUpdateRequest $request, CourseCategory $courseCategory)
    {
        $courseCategory->update($request->validated());

        return new CourseCategoryResource($courseCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CourseCategory $courseCategory)
    {
        if ($courseCategory->courses()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with existing courses'
            ], 422);
        }

        $courseCategory->delete();

        return response()->json([
            'message' => 'Course category deleted successfully'
        ]);
    }
}
