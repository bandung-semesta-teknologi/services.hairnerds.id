<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerBankStoreRequest;
use App\Http\Requests\AnswerBankUpdateRequest;
use App\Http\Resources\AnswerBankResource;
use App\Models\AnswerBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnswerBankController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AnswerBank::class);

        $user = $request->user();

        $answerBanks = AnswerBank::query()
            ->with(['question.quiz.course'])
            ->when($user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('question.quiz.course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->question_id, fn($q) => $q->where('question_id', $request->question_id))
            ->when($request->is_correct !== null, function($q) use ($request) {
                return $request->boolean('is_correct') ? $q->correct() : $q->incorrect();
            })
            ->when($request->search, fn($q) => $q->where('answer', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return AnswerBankResource::collection($answerBanks);
    }

    public function store(AnswerBankStoreRequest $request)
    {
        $this->authorize('create', AnswerBank::class);

        try {
            $answerBank = AnswerBank::create($request->validated());
            $answerBank->load(['question.quiz.course']);

            return response()->json([
                'status' => 'success',
                'message' => 'Answer created successfully',
                'data' => new AnswerBankResource($answerBank)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating answer: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create answer'
            ], 500);
        }
    }

    public function show(AnswerBank $answerBank)
    {
        $this->authorize('view', $answerBank);

        $answerBank->load(['question.quiz.course']);

        return new AnswerBankResource($answerBank);
    }

    public function update(AnswerBankUpdateRequest $request, AnswerBank $answerBank)
    {
        $this->authorize('update', $answerBank);

        try {
            $answerBank->update($request->validated());
            $answerBank->load(['question.quiz.course']);

            return response()->json([
                'status' => 'success',
                'message' => 'Answer updated successfully',
                'data' => new AnswerBankResource($answerBank)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating answer: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update answer'
            ], 500);
        }
    }

    public function destroy(AnswerBank $answerBank)
    {
        $this->authorize('delete', $answerBank);

        try {
            $answerBank->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Answer deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting answer: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete answer'
            ], 500);
        }
    }
}
