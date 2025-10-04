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
            $rules['attachment_ids'] = 'nullable|array';
            $rules['attachment_ids.*'] = 'nullable|exists:attachments,id';
            $rules['attachment_types'] = 'nullable|array';
            $rules['attachment_types.*'] = ['required_with:attachment_types', Rule::in(['youtube', 'document', 'text', 'audio', 'live'])];
            $rules['attachment_titles'] = 'nullable|array';
            $rules['attachment_titles.*'] = 'required_with:attachment_titles|string|max:255';

            if ($type === 'document') {
                $rules['attachment_files'] = 'nullable|array';
                $rules['attachment_files.*'] = 'nullable|file|mimes:pdf,doc,docx,ppt,pptx|max:10240';
            } elseif ($type === 'audio') {
                $rules['attachment_files'] = 'nullable|array';
                $rules['attachment_files.*'] = 'nullable|file|mimes:mp3,wav,ogg|max:10240';
            }

            $rules['attachment_urls'] = 'nullable|array';
            $rules['attachment_urls.*'] = 'nullable|string|max:255';
        } else {
            $rules['attachment_ids'] = 'nullable|array';
            $rules['attachment_ids.*'] = 'nullable|exists:attachments,id';
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

            if ($this->has('attachment_ids') && $this->has('attachment_types')) {
                $idsCount = count(array_filter($this->attachment_ids ?? [], fn($id) => $id !== null));
                $typesCount = count($this->attachment_types ?? []);

                if ($idsCount > $typesCount) {
                    $validator->errors()->add('attachments', 'Attachment IDs count cannot exceed types count');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'attachment_files.*.max' => 'File size must not exceed 10MB',
            'attachment_files.*.mimes' => 'Invalid file type',
        ];
    }
}
