<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseFaqStoreRequest;
use App\Http\Requests\CourseFaqUpdateRequest;
use App\Http\Resources\CourseFaqResource;
use App\Models\Course;
use App\Models\CourseFaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseFaqController extends Controller
{
    public function index(Request $request)
    {
        $faqs = CourseFaq::query()
            ->with('course')
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->paginate($request->per_page ?? 15);

        return CourseFaqResource::collection($faqs);
    }

    public function store(CourseFaqStoreRequest $request)
    {
        try {
            $faq = CourseFaq::create($request->validated());
            $faq->load('course');

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ created successfully',
                'data' => new CourseFaqResource($faq)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating FAQ: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create FAQ'
            ], 500);
        }
    }

    public function show(CourseFaq $coursesFaq)
    {
        $coursesFaq->load('course');

        return new CourseFaqResource($coursesFaq);
    }

    public function update(CourseFaqUpdateRequest $request, CourseFaq $coursesFaq)
    {
        try {
            $coursesFaq->update($request->validated());
            $coursesFaq->load('course');

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ updated successfully',
                'data' => new CourseFaqResource($coursesFaq)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating FAQ: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update FAQ'
            ], 500);
        }
    }

    public function destroy(CourseFaq $coursesFaq)
    {
        try {
            $coursesFaq->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting FAQ: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete FAQ'
            ], 500);
        }
    }
}
