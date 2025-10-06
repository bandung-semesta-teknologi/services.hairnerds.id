<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'section_id' => 'required|exists:sections,id',
            'course_id' => 'required|exists:courses,id',
            'sequence' => 'required|integer|min:1',
            'type' => ['required', Rule::in(['youtube', 'document', 'text', 'audio', 'live', 'quiz'])],
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'datetime' => 'nullable|date',
        ];

        if (in_array($this->type, ['document', 'audio'])) {
            $rules['attachment_types'] = 'required|array|min:1';
            $rules['attachment_types.*'] = ['required', Rule::in(['youtube', 'document', 'text', 'audio', 'live'])];
            $rules['attachment_titles'] = 'required|array|min:1';
            $rules['attachment_titles.*'] = 'required|string|max:255';

            if ($this->type === 'document') {
                $rules['attachment_files'] = 'required|array|min:1';
                $rules['attachment_files.*'] = 'required|file|mimes:pdf,doc,docx,ppt,pptx|max:10240';
            } elseif ($this->type === 'audio') {
                $rules['attachment_files'] = 'required|array|min:1';
                $rules['attachment_files.*'] = 'required|file|mimes:mp3,wav,ogg|max:10240';
            }

            $rules['attachment_urls'] = 'nullable|array';
            $rules['attachment_urls.*'] = 'nullable|string|max:255';
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
            'attachments.required' => 'At least one attachment is required for document or audio lesson type',
            'attachments.*.file.max' => 'File size must not exceed 10MB',
            'attachments.*.file.mimes' => 'Invalid file type',
        ];
    }
}
