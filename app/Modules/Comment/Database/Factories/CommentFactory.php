<?php

declare(strict_types=1);

namespace App\Modules\Comment\Database\Factories;

use App\Models\User;
use App\Modules\Comment\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Comment>
 */
final class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entity_type' => 'product',
            'entity_uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->sentence(),
            'status' => (string) config('shop.comment_status_roles.visible'),
        ];
    }
}
