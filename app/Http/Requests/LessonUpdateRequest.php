<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'section_id' => 'sometimes|required|exists:sections,id',
            'course_id' => 'sometimes|required|exists:courses,id',
            'sequence' => 'sometimes|required|integer|min:1',
            'type' => ['sometimes', 'required', Rule::in(['youtube', 'document', 'text', 'audio', 'live', 'quiz'])],
            'title' => 'sometimes|required|string|max:255',
            'url' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'datetime' => 'nullable|date',
        ];

        $type = $this->type ?? $this->route('lesson')->type;

        if (in_array($type, ['document', 'audio'])) {
            $lesson = $this->route('lesson');
            $hasExistingAttachments = $lesson && $lesson->attachments()->count() > 0;

            if (!$hasExistingAttachments || $this->has('attachment_types')) {
                $rules['attachment_types'] = 'sometimes|array|min:1';
                $rules['attachment_types.*'] = ['required_with:attachment_types', Rule::in(['youtube', 'document', 'text', 'audio', 'live'])];
                $rules['attachment_titles'] = 'sometimes|array|min:1';
                $rules['attachment_titles.*'] = 'required_with:attachment_titles|string|max:255';

                if ($type === 'document') {
                    $rules['attachment_files'] = 'sometimes|array';
                    $rules['attachment_files.*'] = 'sometimes|file|mimes:pdf,doc,docx,ppt,pptx|max:10240';
                } elseif ($type === 'audio') {
                    $rules['attachment_files'] = 'sometimes|array';
                    $rules['attachment_files.*'] = 'sometimes|file|mimes:mp3,wav,ogg|max:10240';
                }

                $rules['attachment_urls'] = 'nullable|array';
                $rules['attachment_urls.*'] = 'nullable|string|max:255';
            }
        } else {
            $rules['attachment_types'] = 'nullable|array';
            $rules['attachment_types.*'] = ['required_with:attachment_types', Rule::in(['youtube', 'document', 'text', 'audio', 'live'])];
            $rules['attachment_titles'] = 'nullable|array';
            $rules['attachment_titles.*'] = 'required_with:attachment_titles|string|max:255';
            $rules['attachment_files'] = 'nullable|array';
            $rules['attachment_files.*'] = 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,mp3,wav,ogg|max:10240';
            $rules['attachment_urls'] = 'nullable|array';
            $rules['attachment_urls.*'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('attachment_types') && $this->has('attachment_titles')) {
                $typesCount = count($this->attachment_types ?? []);
                $titlesCount = count($this->attachment_titles ?? []);

                if ($typesCount !== $titlesCount) {
                    $validator->errors()->add('attachments', 'Attachment types and titles count must match');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'attachments.min' => 'At least one attachment is required for document or audio lesson type',
            'attachments.*.file.max' => 'File size must not exceed 10MB',
            'attachments.*.file.mimes' => 'Invalid file type',
        ];
    }
}
