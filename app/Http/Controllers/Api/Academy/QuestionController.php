<?php

namespace App\Http\Controllers\Api\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\QuestionStoreRequest;
use App\Http\Requests\Academy\QuestionUpdateRequest;
use App\Http\Resources\Academy\QuestionResource;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Question::class);

        $user = $request->user();

        $questions = Question::query()
            ->with(['quiz', 'answerBanks'])
            ->when($user->role === 'student', function($q) use ($user) {
                return $q->whereHas('quiz.course', function($q) {
                    $q->where('status', 'published');
                })->whereHas('quiz.course.enrollments', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->when($user->role === 'instructor', function($q) use ($user) {
                return $q->whereHas('quiz.course.instructors', fn($q) => $q->where('users.id', $user->id));
            })
            ->when($request->quiz_id, fn($q) => $q->where('quiz_id', $request->quiz_id))
            ->when($request->type, fn($q) => $q->byType($request->type))
            ->when($request->search, fn($q) => $q->where('question', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return QuestionResource::collection($questions);
    }

    public function store(QuestionStoreRequest $request)
    {
        $this->authorize('create', Question::class);

        try {
            return DB::transaction(function () use ($request) {
                $quiz = Quiz::findOrFail($request->quiz_id);

                if ($request->user()->role === 'instructor') {
                    if (!$quiz->course->instructors->contains($request->user())) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Unauthorized to create questions for this quiz'
                        ], 403);
                    }
                }

                $questionData = $request->only(['quiz_id', 'type', 'question', 'score']);
                $question = Question::create($questionData);

                if ($request->has('answers')) {
                    $answersData = collect($request->answers)->map(function ($answer) use ($question) {
                        return [
                            'question_id' => $question->id,
                            'answer' => $answer['answer'],
                            'is_true' => $answer['is_true'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    });

                    $question->answerBanks()->insert($answersData->toArray());
                }

                $question->load(['quiz', 'answerBanks']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Question created successfully',
                    'data' => new QuestionResource($question)
                ], 201);
            });
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
        $this->authorize('view', $question);

        $question->load(['quiz', 'answerBanks']);

        return new QuestionResource($question);
    }

    public function update(QuestionUpdateRequest $request, Question $question)
    {
        $this->authorize('update', $question);

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
        $this->authorize('delete', $question);

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
