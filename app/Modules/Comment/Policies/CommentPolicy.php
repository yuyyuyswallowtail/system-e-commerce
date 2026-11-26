<?php

declare(strict_types=1);

namespace App\Modules\Comment\Policies;

use App\Models\User;
use App\Modules\Comment\Models\Comment;

final class CommentPolicy
{
    private const MODERATE_PERMISSION = 'comments.moderate';

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id && $comment->isVisible() && ! $comment->trashed();
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id && ! $comment->trashed();
    }

    public function moderate(User $user): bool
    {
        return $user->can(self::MODERATE_PERMISSION);
    }
}
