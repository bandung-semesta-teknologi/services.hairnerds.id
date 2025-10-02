<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizLessonStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => 'required|exists:sections,id',
            'course_id' => 'required|exists:courses,id',
            'sequence' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'datetime' => 'nullable|date',

            'quiz' => 'required|array',
            'quiz.title' => 'required|string|max:255',
            'quiz.instruction' => 'nullable|string',
            'quiz.duration' => 'nullable|date_format:H:i:s',
            'quiz.total_marks' => 'nullable|integer|min:0',
            'quiz.pass_marks' => 'nullable|integer|min:0',
            'quiz.max_retakes' => 'nullable|integer|min:0',
            'quiz.min_lesson_taken' => 'nullable|integer|min:0',

            'quiz.questions' => 'required|array|min:1',
            'quiz.questions.*.type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'fill_blank'])],
            'quiz.questions.*.question' => 'required|string',
            'quiz.questions.*.score' => 'nullable|integer|min:0',

            'quiz.questions.*.answers' => 'required|array|min:1',
            'quiz.questions.*.answers.*.answer' => 'required|string|max:255',
            'quiz.questions.*.answers.*.is_true' => 'required|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('quiz.questions')) {
                foreach ($this->input('quiz.questions', []) as $index => $question) {
                    $answers = $question['answers'] ?? [];
                    $trueCount = collect($answers)->where('is_true', true)->count();

                    $type = $question['type'] ?? '';

                    if ($type === 'single_choice' && $trueCount !== 1) {
                        $validator->errors()->add(
                            "quiz.questions.{$index}.answers",
                            'Single choice question must have exactly one correct answer'
                        );
                    }

                    if ($type === 'multiple_choice' && $trueCount < 1) {
                        $validator->errors()->add(
                            "quiz.questions.{$index}.answers",
                            'Multiple choice question must have at least one correct answer'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'quiz.required' => 'Quiz data is required for quiz type lesson',
            'quiz.questions.required' => 'At least one question is required',
            'quiz.questions.min' => 'At least one question is required',
            'quiz.questions.*.answers.required' => 'Each question must have answers',
            'quiz.questions.*.answers.min' => 'Each question must have at least one answer',
        ];
    }
}
