<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $types = ['youtube', 'document', 'text', 'audio', 'live', 'quiz'];
        $selectedType = $this->faker->randomElement($types);

        $titles = [
            'youtube' => [
                'Introduction Video',
                'Step-by-Step Tutorial',
                'Advanced Techniques Demo',
                'Common Mistakes to Avoid'
            ],
            'document' => [
                'Course Manual PDF',
                'Reference Guide',
                'Practice Worksheets',
                'Certification Requirements'
            ],
            'text' => [
                'Theory and Concepts',
                'Historical Background',
                'Best Practices Guide',
                'Key Terminology'
            ],
            'audio' => [
                'Expert Interview',
                'Guided Practice Session',
                'Success Stories Podcast',
                'Q&A Recording'
            ],
            'live' => [
                'Live Practice Session',
                'Interactive Workshop',
                'Q&A Session',
                'Group Discussion'
            ],
            'quiz' => [
                'Knowledge Check Quiz',
                'Practice Assessment',
                'Final Exam',
                'Skills Evaluation'
            ]
        ];

        $urls = [
            'youtube' => 'https://www.youtube.com/watch?v=' . $this->faker->regexify('[A-Za-z0-9_-]{11}'),
            'document' => 'https://example.com/documents/' . $this->faker->slug . '.pdf',
            'text' => 'https://example.com/articles/' . $this->faker->slug,
            'audio' => 'https://example.com/audio/' . $this->faker->slug . '.mp3',
            'live' => 'https://zoom.us/j/' . $this->faker->numerify('###########'),
            'quiz' => 'https://example.com/quiz/' . $this->faker->slug
        ];

        return [
            'section_id' => Section::factory(),
            'course_id' => Course::factory(),
            'sequence' => $this->faker->numberBetween(1, 20),
            'type' => $selectedType,
            'title' => $this->faker->randomElement($titles[$selectedType]),
            'url' => $urls[$selectedType],
            'summary' => $this->faker->optional(0.7)->paragraphs(2, true),
            'datetime' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
        ];
    }

    public function youtube()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'youtube',
            'url' => 'https://www.youtube.com/watch?v=' . $this->faker->regexify('[A-Za-z0-9_-]{11}'),
        ]);
    }

    public function document()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'document',
            'url' => 'https://example.com/documents/' . $this->faker->slug . '.pdf',
        ]);
    }

    public function quiz()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'quiz',
            'url' => 'https://example.com/quiz/' . $this->faker->slug,
        ]);
    }
}
