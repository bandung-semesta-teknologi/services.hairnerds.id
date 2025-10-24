<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'fill_blank'])],
            'answers.*.answers' => 'required',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('answers')) {
                foreach ($this->input('answers', []) as $index => $answer) {
                    $type = $answer['type'] ?? '';
                    $answerValue = $answer['answers'] ?? null;

                    if ($type === 'single_choice') {
                        if (!is_numeric($answerValue)) {
                            $validator->errors()->add(
                                "answers.{$index}.answers",
                                'Single choice answer must be a number (answer ID)'
                            );
                        }
                    }

                    if ($type === 'multiple_choice') {
                        if (!is_array($answerValue)) {
                            $validator->errors()->add(
                                "answers.{$index}.answers",
                                'Multiple choice answer must be an array of answer IDs'
                            );
                        } else {
                            foreach ($answerValue as $subIndex => $value) {
                                if (!is_numeric($value)) {
                                    $validator->errors()->add(
                                        "answers.{$index}.answers.{$subIndex}",
                                        'Each multiple choice answer must be a number (answer ID)'
                                    );
                                }
                            }
                        }
                    }

                    if ($type === 'fill_blank') {
                        if (!is_string($answerValue)) {
                            $validator->errors()->add(
                                "answers.{$index}.answers",
                                'Fill blank answer must be a string'
                            );
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Quiz answers are required',
            'answers.min' => 'At least one answer is required',
            'answers.*.question_id.required' => 'Question ID is required for each answer',
            'answers.*.question_id.exists' => 'Question ID must exist in the database',
            'answers.*.type.required' => 'Question type is required for each answer',
            'answers.*.type.in' => 'Question type must be one of: single_choice, multiple_choice, fill_blank',
            'answers.*.answers.required' => 'Answer value is required',
        ];
    }
}
