<?php

declare(strict_types=1);

namespace App\Modules\Comment\Services;

use App\Models\User;
use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Comment\Events\CommentPosted;
use App\Modules\Comment\Models\Comment;
use App\Modules\Comment\Services\Concerns\ValidatesInput;
use App\Modules\Content\Contracts\ArticleDirectory;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

final class CommentService
{
    use ValidatesInput;

    public function __construct(
        private readonly ProductCatalog $products,
        private readonly ArticleDirectory $articles,
    ) {}

    public function post(User $actor, string $entityType, string $entityUuid, string $body, ?string $parentUuid = null): Comment
    {
        $valid = $this->validate(
            ['entity_type' => $entityType, 'entity_uuid' => $entityUuid, 'body' => trim($body)],
            [
                'entity_type' => ['required', 'string', Rule::in((array) config('shop.comment_entity_types'))],
                'entity_uuid' => ['required', 'string', 'max:36'],
                'body' => $this->bodyRules(),
            ],
        );

        if (! $this->targetExists($valid['entity_type'], $valid['entity_uuid'])) {
            throw new DomainValidationException(['entity_uuid' => 'The comment target does not exist.']);
        }

        $parent = $parentUuid === null || $parentUuid === '' ? null : $this->resolveParent($parentUuid, $valid['entity_type'], $valid['entity_uuid']);

        $key = $this->floodKey($actor);

        if (RateLimiter::tooManyAttempts($key, (int) config('shop.comment_flood_limit'))) {
            throw new ConflictException('You are commenting too fast. Try again in '.RateLimiter::availableIn($key).' seconds.');
        }

        $comment = DB::transaction(fn (): Comment => Comment::create([
            'entity_type' => $valid['entity_type'],
            'entity_uuid' => $valid['entity_uuid'],
            'user_id' => $actor->id,
            'parent_id' => $parent?->id,
            'body' => $valid['body'],
            'status' => Comment::visibleStatus(),
        ]));

        RateLimiter::hit($key, (int) config('shop.comment_flood_decay_seconds'));

        CommentPosted::dispatch($comment, $actor->id, $parent?->uuid);

        return $comment;
    }

    public function editOwn(User $actor, Comment $comment, string $body): Comment
    {
        Gate::forUser($actor)->authorize('update', $comment);

        $valid = $this->validate(['body' => trim($body)], ['body' => $this->bodyRules()]);

        DB::transaction(function () use ($comment, $valid): void {
            $comment->fill(['body' => $valid['body']])->save();
        });

        return $comment;
    }

    public function deleteOwn(User $actor, Comment $comment): void
    {
        Gate::forUser($actor)->authorize('delete', $comment);

        DB::transaction(function () use ($comment): void {
            $comment->delete();
        });
    }

    public function hide(User $actor, Comment $comment): Comment
    {
        return $this->setStatus($actor, $comment, Comment::hiddenStatus(), 'The comment is already hidden.');
    }

    public function show(User $actor, Comment $comment): Comment
    {
        return $this->setStatus($actor, $comment, Comment::visibleStatus(), 'The comment is already visible.');
    }

    public function deleteByModerator(User $actor, Comment $comment): void
    {
        Gate::forUser($actor)->authorize('moderate', Comment::class);

        DB::transaction(function () use ($comment): void {
            $comment->delete();
        });
    }

    /**
     * Visible top-level comments of one entity with their visible replies.
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function visibleThread(string $entityType, string $entityUuid, int $perPage): LengthAwarePaginator
    {
        $visible = Comment::visibleStatus();

        return Comment::query()
            ->where('entity_type', $entityType)
            ->where('entity_uuid', $entityUuid)
            ->whereNull('parent_id')
            ->where('status', $visible)
            ->with([
                'user',
                'replies' => fn ($query) => $query->where('status', $visible)->orderBy('created_at')->orderBy('id')->with('user'),
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($perPage);
    }

    private function setStatus(User $actor, Comment $comment, string $status, string $conflict): Comment
    {
        Gate::forUser($actor)->authorize('moderate', Comment::class);

        if ($comment->status === $status) {
            throw new ConflictException($conflict);
        }

        DB::transaction(function () use ($comment, $status): void {
            $comment->fill(['status' => $status])->save();
        });

        return $comment;
    }

    private function targetExists(string $entityType, string $entityUuid): bool
    {
        $result = match ($entityType) {
            'product' => $this->products->exists([$entityUuid]),
            'article' => $this->articles->exists([$entityUuid]),
            default => [],
        };

        return ($result[$entityUuid] ?? false) === true;
    }

    private function resolveParent(string $parentUuid, string $entityType, string $entityUuid): Comment
    {
        $parent = Comment::query()->where('uuid', $parentUuid)->first();

        if ($parent === null || $parent->entity_type !== $entityType || $parent->entity_uuid !== $entityUuid || ! $parent->isVisible()) {
            throw new DomainValidationException(['parent' => 'The comment you reply to does not exist.']);
        }

        if ($parent->parent_id !== null) {
            throw new DomainValidationException(['parent' => 'Replies to replies are not allowed.']);
        }

        return $parent;
    }

    private function floodKey(User $actor): string
    {
        return 'comment-post:'.$actor->id;
    }

    /**
     * @return array<int, string>
     */
    private function bodyRules(): array
    {
        return ['required', 'string', 'max:'.(int) config('shop.comment_max_length')];
    }
}
