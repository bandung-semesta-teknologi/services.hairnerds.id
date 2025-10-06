<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachmentBulkStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_id' => 'required|exists:lessons,id',
            'attachments' => 'required|array|min:1',
            'attachments.*.type' => ['required', Rule::in(['youtube', 'document', 'text', 'audio', 'live'])],
            'attachments.*.title' => 'required|string|max:255',
            'attachments.*.url' => 'nullable|string|max:255',
            'attachments.*.file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,mp3,wav,ogg|max:10240',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $attachments = $this->input('attachments', []);

            foreach ($attachments as $index => $attachment) {
                $type = $attachment['type'] ?? null;

                if (in_array($type, ['document', 'audio'])) {
                    $hasFile = $this->hasFile("attachments.{$index}.file");
                    $hasUrl = !empty($attachment['url']);

                    if (!$hasFile && !$hasUrl) {
                        $validator->errors()->add(
                            "attachments.{$index}",
                            "Either file or url is required for {$type} attachment"
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'attachments.required' => 'At least one attachment is required',
            'attachments.array' => 'Attachments must be an array',
            'attachments.min' => 'At least one attachment is required',
            'attachments.*.type.required' => 'Type is required for each attachment',
            'attachments.*.title.required' => 'Title is required for each attachment',
            'attachments.*.file.max' => 'File size must not exceed 10MB',
            'attachments.*.file.mimes' => 'Invalid file type',
        ];
    }
}
