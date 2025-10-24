<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lessons' => 'required|array|min:1',
            'lessons.*.id' => 'nullable|exists:lessons,id',
            'lessons.*.sequence' => 'required|integer|min:1',
            'lessons.*.type' => ['required', Rule::in(['youtube', 'document', 'text', 'audio', 'live', 'quiz'])],
            'lessons.*.title' => 'required|string|max:255',
            'lessons.*.url' => 'nullable|string|max:255',
            'lessons.*.summary' => 'nullable|string',
            'lessons.*.datetime' => 'nullable|date',

            'lessons.*.attachments' => 'nullable|array',
            'lessons.*.attachments.*.id' => 'nullable|exists:attachments,id',
            'lessons.*.attachments.*.type' => ['required_with:lessons.*.attachments', Rule::in(['youtube', 'document', 'text', 'audio', 'live'])],
            'lessons.*.attachments.*.title' => 'required_with:lessons.*.attachments|string|max:255',
            'lessons.*.attachments.*.url' => 'nullable|string|max:255',
            'lessons.*.attachments.*.file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,mp3,wav,ogg|max:10240',

            'lessons.*.quiz' => 'nullable|array',
            'lessons.*.quiz.id' => 'nullable|exists:quizzes,id',
            'lessons.*.quiz.title' => 'required_with:lessons.*.quiz|string|max:255',
            'lessons.*.quiz.instruction' => 'nullable|string',
            'lessons.*.quiz.duration' => 'nullable|date_format:H:i:s',
            'lessons.*.quiz.total_marks' => 'nullable|integer|min:0',
            'lessons.*.quiz.pass_marks' => 'nullable|integer|min:0',
            'lessons.*.quiz.max_retakes' => 'nullable|integer|min:0',
            'lessons.*.quiz.min_lesson_taken' => 'nullable|integer|min:0',

            'lessons.*.quiz.questions' => 'nullable|array',
            'lessons.*.quiz.questions.*.id' => 'nullable|exists:questions,id',
            'lessons.*.quiz.questions.*.type' => ['required_with:lessons.*.quiz.questions', Rule::in(['single_choice', 'multiple_choice', 'fill_blank'])],
            'lessons.*.quiz.questions.*.question' => 'required_with:lessons.*.quiz.questions|string',
            'lessons.*.quiz.questions.*.score' => 'nullable|integer|min:0',

            'lessons.*.quiz.questions.*.answers' => 'nullable|array|min:1',
            'lessons.*.quiz.questions.*.answers.*.id' => 'nullable|exists:answer_banks,id',
            'lessons.*.quiz.questions.*.answers.*.answer' => 'required_with:lessons.*.quiz.questions.*.answers|string|max:255',
            'lessons.*.quiz.questions.*.answers.*.is_true' => 'required_with:lessons.*.quiz.questions.*.answers|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lessons = $this->input('lessons', []);

            foreach ($lessons as $index => $lesson) {
                $type = $lesson['type'] ?? null;

                if (in_array($type, ['document', 'audio'])) {
                    if (empty($lesson['attachments'])) {
                        $validator->errors()->add(
                            "lessons.{$index}.attachments",
                            "Attachments are required for {$type} lesson type"
                        );
                    }
                }

                if ($type === 'quiz') {
                    if (empty($lesson['quiz'])) {
                        $validator->errors()->add(
                            "lessons.{$index}.quiz",
                            "Quiz data is required for quiz lesson type"
                        );
                    } elseif (empty($lesson['quiz']['questions'])) {
                        $validator->errors()->add(
                            "lessons.{$index}.quiz.questions",
                            "Questions are required for quiz"
                        );
                    }
                }

                if (isset($lesson['attachments'])) {
                    foreach ($lesson['attachments'] as $attIndex => $attachment) {
                        $attType = $attachment['type'] ?? null;
                        $hasId = !empty($attachment['id']);

                        if (in_array($attType, ['document', 'audio']) && !$hasId) {
                            $hasFile = $this->hasFile("lessons.{$index}.attachments.{$attIndex}.file");
                            $hasUrl = !empty($attachment['url']);

                            if (!$hasFile && !$hasUrl) {
                                $validator->errors()->add(
                                    "lessons.{$index}.attachments.{$attIndex}",
                                    "Either file or url is required for new {$attType} attachment"
                                );
                            }
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'lessons.required' => 'Lessons data is required',
            'lessons.array' => 'Lessons must be an array',
            'lessons.min' => 'At least one lesson is required',
            'lessons.*.attachments.*.file.max' => 'File size must not exceed 10MB',
            'lessons.*.attachments.*.file.mimes' => 'Invalid file type',
        ];
    }
}
