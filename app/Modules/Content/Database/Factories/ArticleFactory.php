<?php

declare(strict_types=1);

namespace App\Modules\Content\Database\Factories;

use App\Models\User;
use App\Modules\Content\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
final class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'summary' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'cover_path' => null,
            'status' => (string) config('shop.article.statuses.draft'),
            'author_id' => User::factory(),
            'published_at' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
