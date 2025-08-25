<?php

namespace Database\Seeders;

use App\Models\Bootcamp;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class BootcampSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = User::where('role', 'instructor')->get();
        $categories = Category::all();

        if ($instructors->isEmpty()) {
            $instructors = User::factory()->instructor()->count(3)->create();
        }

        if ($categories->isEmpty()) {
            $categories = Category::factory()->count(5)->create();
        }

        foreach ($instructors as $instructor) {
            Bootcamp::factory()->count(3)->published()->verified()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) use ($categories) {
                $bootcamp->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
            });

            Bootcamp::factory()->count(2)->draft()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) use ($categories) {
                $bootcamp->categories()->attach($categories->random(rand(1, 2))->pluck('id'));
            });

            Bootcamp::factory()->count(1)->rejected()->verified()->create([
                'user_id' => $instructor->id
            ])->each(function ($bootcamp) use ($categories) {
                $bootcamp->categories()->attach($categories->random(1)->pluck('id'));
            });
        }

        Bootcamp::factory()->count(3)->unpublished()->verified()->create()->each(function ($bootcamp) use ($categories) {
            $bootcamp->categories()->attach($categories->random(rand(1, 2))->pluck('id'));
        });
    }
}
