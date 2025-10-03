<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Section;

class SectionUpdateSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sections' => 'required|array|min:1',
            'sections.*.id' => 'required|exists:sections,id',
            'sections.*.sequence' => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sectionIds = collect($this->input('sections'))->pluck('id')->toArray();

            if (empty($sectionIds)) {
                return;
            }

            $sections = Section::whereIn('id', $sectionIds)->get();

            if ($sections->isEmpty()) {
                $validator->errors()->add('sections', 'No valid sections found');
                return;
            }

            $courseIds = $sections->pluck('course_id')->unique();

            if ($courseIds->count() > 1) {
                $validator->errors()->add('sections', 'All sections must belong to the same course');
            }

            $sequences = collect($this->input('sections'))->pluck('sequence')->toArray();
            if (count($sequences) !== count(array_unique($sequences))) {
                $validator->errors()->add('sections', 'Sequence numbers must be unique');
            }

            $user = $this->user();
            if ($user && $user->role === 'instructor') {
                $courseId = $courseIds->first();
                $course = \App\Models\Course::with('instructors')->find($courseId);

                if ($course && !$course->instructors->contains($user)) {
                    $validator->errors()->add('sections', 'You are not authorized to update sections for this course');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'sections.required' => 'Sections data is required',
            'sections.array' => 'Sections must be an array',
            'sections.min' => 'At least one section is required',
            'sections.*.id.required' => 'Section ID is required',
            'sections.*.id.exists' => 'One or more section IDs are invalid',
            'sections.*.sequence.required' => 'Sequence is required for each section',
            'sections.*.sequence.integer' => 'Sequence must be an integer',
            'sections.*.sequence.min' => 'Sequence must be at least 1',
        ];
    }
}
