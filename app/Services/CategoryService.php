<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function resolveCategoryIds(array $categories): array
    {
        $resolvedIds = [];

        foreach ($categories as $category) {
            if (is_numeric($category)) {
                $resolvedIds[] = (int) $category;
            } elseif (is_string($category)) {
                $resolvedIds[] = $this->findOrCreateCategory($category);
            }
        }

        return array_unique($resolvedIds);
    }

    protected function findOrCreateCategory(string $name): int
    {
        $name = trim($name);

        $category = Category::where('name', $name)->first();

        if (!$category) {
            $category = Category::create(['name' => $name]);
        }

        return $category->id;
    }
}
