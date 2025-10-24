<?php

namespace App\Http\Controllers\Api\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\QuizLessonStoreRequest;
use App\Http\Requests\Academy\QuizLessonUpdateRequest;
use App\Http\Resources\Academy\LessonResource;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizLessonController extends Controller
{
    public function store(QuizLessonStoreRequest $request)
    {
        $this->authorize('create', Lesson::class);

        try {
            return DB::transaction(function () use ($request) {
                $lessonData = $request->only([
                    'section_id',
                    'course_id',
                    'sequence',
                    'title',
                    'url',
                    'summary',
                    'datetime'
                ]);

                $lessonData['type'] = 'quiz';

                $lesson = Lesson::create($lessonData);

                $quizData = $request->input('quiz');
                $questionsData = $quizData['questions'] ?? [];
                unset($quizData['questions']);

                $quizData['section_id'] = $lesson->section_id;
                $quizData['lesson_id'] = $lesson->id;
                $quizData['course_id'] = $lesson->course_id;

                $quiz = Quiz::create($quizData);

                foreach ($questionsData as $questionData) {
                    $answersData = $questionData['answers'] ?? [];
                    unset($questionData['answers']);

                    $questionData['quiz_id'] = $quiz->id;

                    $question = $quiz->questions()->create($questionData);

                    foreach ($answersData as $answerData) {
                        $answerData['question_id'] = $question->id;
                        $question->answerBanks()->create($answerData);
                    }
                }

                $lesson->load([
                    'section',
                    'course',
                    'quiz.questions.answerBanks'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Quiz lesson created successfully',
                    'data' => new LessonResource($lesson)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error creating quiz lesson: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create quiz lesson',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(QuizLessonUpdateRequest $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson);

        if ($lesson->type !== 'quiz') {
            return response()->json([
                'status' => 'error',
                'message' => 'This endpoint only accepts quiz type lessons'
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $lesson) {
                $lessonData = array_filter([
                    'section_id' => $request->input('section_id'),
                    'course_id' => $request->input('course_id'),
                    'sequence' => $request->input('sequence'),
                    'title' => $request->input('title'),
                    'url' => $request->input('url'),
                    'summary' => $request->input('summary'),
                    'datetime' => $request->input('datetime'),
                ], function($value) {
                    return $value !== null;
                });

                if (!empty($lessonData)) {
                    $lesson->update($lessonData);
                }

                if ($request->has('quiz')) {
                    $quizData = $request->input('quiz');
                    $questionsData = $quizData['questions'] ?? null;
                    unset($quizData['questions']);

                    $quiz = $lesson->quiz;

                    if (!$quiz) {
                        $quizData['section_id'] = $lesson->section_id;
                        $quizData['lesson_id'] = $lesson->id;
                        $quizData['course_id'] = $lesson->course_id;

                        $quiz = Quiz::create($quizData);
                    } else {
                        $quizUpdateData = array_filter([
                            'section_id' => $request->input('quiz.section_id', $lesson->section_id),
                            'course_id' => $request->input('quiz.course_id', $lesson->course_id),
                            'title' => $request->input('quiz.title'),
                            'instruction' => $request->input('quiz.instruction'),
                            'duration' => $request->input('quiz.duration'),
                            'total_marks' => $request->input('quiz.total_marks'),
                            'pass_marks' => $request->input('quiz.pass_marks'),
                            'max_retakes' => $request->input('quiz.max_retakes'),
                            'min_lesson_taken' => $request->input('quiz.min_lesson_taken'),
                        ], function($value) {
                            return $value !== null;
                        });

                        if (!empty($quizUpdateData)) {
                            $quiz->update($quizUpdateData);
                        }
                    }

                    if ($questionsData !== null) {
                        foreach ($quiz->questions as $question) {
                            $question->answerBanks()->delete();
                        }
                        $quiz->questions()->delete();

                        foreach ($questionsData as $questionData) {
                            $answersData = $questionData['answers'] ?? [];
                            unset($questionData['answers']);

                            $questionData['quiz_id'] = $quiz->id;

                            $question = $quiz->questions()->create($questionData);

                            foreach ($answersData as $answerData) {
                                $answerData['question_id'] = $question->id;
                                $question->answerBanks()->create($answerData);
                            }
                        }
                    }
                }

                $lesson->refresh();
                $lesson->load([
                    'section',
                    'course',
                    'quiz.questions.answerBanks'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Quiz lesson updated successfully',
                    'data' => new LessonResource($lesson)
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating quiz lesson: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update quiz lesson',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
