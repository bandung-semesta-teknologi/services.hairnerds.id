<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'lesson_id' => 'nullable|exists:lessons,id',
            'type' => ['nullable', Rule::in(['youtube', 'document', 'text', 'audio', 'live'])],
            'title' => 'nullable|string|max:255',
        ];

        $type = $this->type ?? $this->route('attachment')->type;

        if ($type === 'youtube') {
            $rules['url'] = 'nullable|url|max:255';
        } elseif ($type === 'document') {
            $rules['file'] = 'nullable|file|mimes:pdf,doc,docx,ppt,pptx|max:10240';
        } elseif ($type === 'audio') {
            $rules['file'] = 'nullable|file|mimes:mp3,wav,ogg|max:10240';
        } elseif ($type === 'text') {
            $rules['url'] = 'nullable|string|max:255';
        } elseif ($type === 'live') {
            $rules['url'] = 'nullable|url|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'file.max' => 'File size must not exceed 10MB',
            'file.mimes' => 'Invalid file type',
        ];
    }
}
