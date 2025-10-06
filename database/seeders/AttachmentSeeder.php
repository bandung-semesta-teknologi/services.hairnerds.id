<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class AttachmentSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = Lesson::whereIn('type', ['document', 'audio'])->get();

        if ($lessons->isEmpty()) {
            $this->command->warn('No document or audio lessons found. Skipping Attachment seeding.');
            return;
        }

        foreach ($lessons as $lesson) {
            $attachmentCount = rand(1, 3);

            for ($i = 0; $i < $attachmentCount; $i++) {
                Attachment::factory()->create([
                    'lesson_id' => $lesson->id,
                    'type' => $lesson->type,
                ]);
            }
        }

        $otherLessons = Lesson::whereNotIn('type', ['document', 'audio', 'quiz'])
            ->inRandomOrder()
            ->limit(5)
            ->get();

        foreach ($otherLessons as $lesson) {
            if (fake()->boolean(40)) {
                Attachment::factory()->create([
                    'lesson_id' => $lesson->id,
                    'type' => fake()->randomElement(['youtube', 'text', 'live']),
                ]);
            }
        }
    }
}
