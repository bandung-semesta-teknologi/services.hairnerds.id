<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;

class QuizResultStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'quiz_id' => 'required|exists:quizzes,id',
        ];

        if ($user && $user->role !== 'student') {
            $rules['user_id'] = 'required|exists:users,id';
            $rules['lesson_id'] = 'nullable|exists:lessons,id';
            $rules['answered'] = 'nullable|integer|min:0';
            $rules['correct_answers'] = 'nullable|integer|min:0';
            $rules['total_obtained_marks'] = 'nullable|integer|min:0';
            $rules['is_submitted'] = 'nullable|boolean';
            $rules['started_at'] = 'nullable|date';
            $rules['finished_at'] = 'nullable|date|after_or_equal:started_at';
        } else {
            $rules['lesson_id'] = 'nullable|exists:lessons,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'quiz_id.required' => 'Quiz ID is required',
            'quiz_id.exists' => 'Selected quiz does not exist',
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'Selected user does not exist',
            'lesson_id.exists' => 'Selected lesson does not exist',
            'answered.integer' => 'Answered must be a number',
            'answered.min' => 'Answered must be at least 0',
            'correct_answers.integer' => 'Correct answers must be a number',
            'correct_answers.min' => 'Correct answers must be at least 0',
            'total_obtained_marks.integer' => 'Total obtained marks must be a number',
            'total_obtained_marks.min' => 'Total obtained marks must be at least 0',
            'is_submitted.boolean' => 'Is submitted must be true or false',
            'started_at.date' => 'Started at must be a valid date',
            'finished_at.date' => 'Finished at must be a valid date',
            'finished_at.after_or_equal' => 'Finished at must be after or equal to started at',
        ];
    }
}
