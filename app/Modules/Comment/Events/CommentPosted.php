<?php

declare(strict_types=1);

namespace App\Modules\Comment\Events;

use App\Modules\Comment\Models\Comment;
use App\Support\Events\DomainEvent;

final class CommentPosted extends DomainEvent
{
    public function __construct(Comment $comment, ?int $actorId = null, ?string $parentUuid = null)
    {
        parent::__construct('comment', (string) $comment->uuid, $actorId, $comment->user_id, [
            'entity_type' => $comment->entity_type,
            'entity_uuid' => $comment->entity_uuid,
            'parent_uuid' => $parentUuid,
        ]);
    }
}
