<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionStoreRequest;
use App\Http\Requests\QuestionUpdateRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $questions = Question::query()
            ->with(['quiz', 'answerBanks'])
            ->when($request->quiz_id, fn($q) => $q->where('quiz_id', $request->quiz_id))
            ->when($request->type, fn($q) => $q->byType($request->type))
            ->when($request->search, fn($q) => $q->where('question', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return QuestionResource::collection($questions);
    }

    public function store(QuestionStoreRequest $request)
    {
        try {
            $question = Question::create($request->validated());
            $question->load(['quiz', 'answerBanks']);

            return response()->json([
                'status' => 'success',
                'message' => 'Question created successfully',
                'data' => new QuestionResource($question)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating question: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create question'
            ], 500);
        }
    }

    public function show(Question $question)
    {
        $question->load(['quiz', 'answerBanks']);

        return new QuestionResource($question);
    }

    public function update(QuestionUpdateRequest $request, Question $question)
    {
        try {
            $question->update($request->validated());
            $question->load(['quiz', 'answerBanks']);

            return response()->json([
                'status' => 'success',
                'message' => 'Question updated successfully',
                'data' => new QuestionResource($question)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating question: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update question'
            ], 500);
        }
    }

    public function destroy(Question $question)
    {
        try {
            $question->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Question deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting question: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete question'
            ], 500);
        }
    }
}
