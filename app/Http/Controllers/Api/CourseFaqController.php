<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseFaqStoreRequest;
use App\Http\Requests\CourseFaqUpdateRequest;
use App\Http\Resources\CourseFaqResource;
use App\Models\Course;
use App\Models\CourseFaq;
use Illuminate\Http\Request;

class CourseFaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $faqs = CourseFaq::query()
            ->with('course')
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->paginate($request->per_page ?? 15);

        return CourseFaqResource::collection($faqs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseFaqStoreRequest $request)
    {
        $faq = CourseFaq::create($request->validated());
        $faq->load('course');

        return new CourseFaqResource($faq);
    }

    /**
     * Display the specified resource.
     */
    public function show(CourseFaq $coursesFaq)
    {
        $coursesFaq->load('course');

        return new CourseFaqResource($coursesFaq);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CourseFaqUpdateRequest $request, CourseFaq $coursesFaq)
    {
        $coursesFaq->update($request->validated());
        $coursesFaq->load('course');

        return new CourseFaqResource($coursesFaq);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CourseFaq $coursesFaq)
    {
        $coursesFaq->delete();

        return response()->json([
            'message' => 'FAQ deleted successfully'
        ]);
    }
}
