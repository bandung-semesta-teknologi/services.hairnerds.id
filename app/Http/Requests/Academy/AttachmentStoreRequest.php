<?php

namespace App\Http\Requests\Academy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'lesson_id' => 'required|exists:lessons,id',
            'type' => ['required', Rule::in(['youtube', 'document', 'text', 'audio', 'live'])],
            'title' => 'required|string|max:255',
        ];

        if ($this->type === 'youtube') {
            $rules['url'] = 'required|url|max:255';
        } elseif ($this->type === 'document') {
            $rules['file'] = 'required|file|mimes:pdf,doc,docx,ppt,pptx|max:10240';
        } elseif ($this->type === 'audio') {
            $rules['file'] = 'required|file|mimes:mp3,wav,ogg|max:10240';
        } elseif ($this->type === 'text') {
            $rules['url'] = 'nullable|string|max:255';
        } elseif ($this->type === 'live') {
            $rules['url'] = 'required|url|max:255';
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
