<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        $types = ['youtube', 'document', 'text', 'audio', 'live'];
        $type = fake()->randomElement($types);

        return [
            'lesson_id' => Lesson::factory(),
            'type' => $type,
            'title' => fake()->sentence(3),
            'url' => $this->generateUrlByType($type),
        ];
    }

    private function generateUrlByType(string $type): string
    {
        return match($type) {
            'youtube' => 'https://www.youtube.com/watch?v=' . fake()->bothify('???########'),
            'document' => 'lessons/attachments/' . fake()->uuid() . '.pdf',
            'text' => 'lessons/attachments/' . fake()->uuid() . '.txt',
            'audio' => 'lessons/attachments/' . fake()->uuid() . '.mp3',
            'live' => 'https://zoom.us/j/' . fake()->numerify('##########'),
            default => fake()->url(),
        };
    }

    public function youtube(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'youtube',
            'url' => 'https://www.youtube.com/watch?v=' . fake()->bothify('???########'),
        ]);
    }

    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'document',
            'url' => 'lessons/attachments/' . fake()->uuid() . '.pdf',
        ]);
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'url' => 'lessons/attachments/' . fake()->uuid() . '.txt',
        ]);
    }

    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'audio',
            'url' => 'lessons/attachments/' . fake()->uuid() . '.mp3',
        ]);
    }

    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'live',
            'url' => 'https://zoom.us/j/' . fake()->numerify('##########'),
        ]);
    }
}
