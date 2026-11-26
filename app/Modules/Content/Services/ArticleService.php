<?php

declare(strict_types=1);

namespace App\Modules\Content\Services;

use App\Models\User;
use App\Modules\Content\Events\ArticlePublished;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Services\Concerns\ValidatesInput;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class ArticleService
{
    use ValidatesInput;

    private const PUBLISH_PERMISSION = 'articles.publish';

    /**
     * @param  array<string, mixed>  $data  title, summary, body
     */
    public function create(User $actor, array $data): Article
    {
        $valid = $this->validate(Arr::only($data, ['title', 'summary', 'body']), $this->textRules());

        return DB::transaction(function () use ($actor, $valid): Article {
            return Article::create([
                'title' => $valid['title'],
                'summary' => $valid['summary'],
                'body' => $valid['body'],
                'status' => (string) config('shop.article.statuses.draft'),
                'author_id' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    /**
     * The slug, status and author are never changed here.
     *
     * @param  array<string, mixed>  $data  title, summary, body
     */
    public function update(User $actor, Article $article, array $data): Article
    {
        $this->assertLive($article);

        $valid = $this->validate(Arr::only($data, ['title', 'summary', 'body']), $this->textRules());

        DB::transaction(function () use ($actor, $article, $valid): void {
            $article->fill([
                'title' => $valid['title'],
                'summary' => $valid['summary'],
                'body' => $valid['body'],
                'updated_by' => $actor->id,
            ])->save();
        });

        return $article;
    }

    public function publish(User $actor, Article $article): Article
    {
        $this->authorizePublish($actor);
        $this->assertLive($article);

        if ($article->status === (string) config('shop.article.statuses.published')) {
            throw new ConflictException('The article is already published.');
        }

        DB::transaction(function () use ($actor, $article): void {
            $article->fill([
                'status' => (string) config('shop.article.statuses.published'),
                'published_at' => Carbon::now(),
                'updated_by' => $actor->id,
            ])->save();
        });

        ArticlePublished::dispatch($article, $actor->id);

        return $article;
    }

    public function unpublish(User $actor, Article $article): Article
    {
        $this->authorizePublish($actor);
        $this->assertLive($article);

        if ($article->status !== (string) config('shop.article.statuses.published')) {
            throw new ConflictException('The article is not published.');
        }

        DB::transaction(function () use ($actor, $article): void {
            $article->fill([
                'status' => (string) config('shop.article.statuses.draft'),
                'published_at' => null,
                'updated_by' => $actor->id,
            ])->save();
        });

        return $article;
    }

    public function archive(User $actor, Article $article): Article
    {
        $this->authorizePublish($actor);
        $this->assertLive($article);

        if ($article->status !== (string) config('shop.article.statuses.published')) {
            throw new ConflictException('Only a published article can be archived.');
        }

        DB::transaction(function () use ($actor, $article): void {
            $article->fill(['status' => (string) config('shop.article.statuses.archived'), 'updated_by' => $actor->id])->save();
        });

        return $article;
    }

    public function delete(User $actor, Article $article): void
    {
        $this->assertLive($article);

        DB::transaction(function () use ($actor, $article): void {
            $article->fill(['updated_by' => $actor->id])->save();
            $article->delete();
        });
    }

    public function restore(User $actor, Article $article): Article
    {
        if (! $article->trashed()) {
            throw new ConflictException('The article is not deleted.');
        }

        DB::transaction(function () use ($actor, $article): void {
            $article->restore();
            $article->fill(['updated_by' => $actor->id])->save();
        });

        return $article;
    }

    public function uploadCover(User $actor, Article $article, UploadedFile $file): Article
    {
        $this->assertLive($article);

        $mimes = array_map('strtolower', (array) config('shop.uploads.mimes'));

        $this->validate(['cover' => $file], [
            'cover' => ['required', 'file', 'mimes:'.implode(',', $mimes), 'max:'.(int) config('shop.uploads.max_kb')],
        ]);

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $mimes, true)) {
            throw new DomainValidationException(['cover' => 'The image type is not allowed.']);
        }

        $disk = Storage::disk((string) config('shop.uploads.disk'));
        $path = $disk->putFileAs((string) config('shop.article.cover_directory'), $file, Str::random(40).'.'.$extension);

        if (! is_string($path)) {
            throw new ConflictException('The cover could not be stored.');
        }

        $old = $article->cover_path;

        try {
            DB::transaction(function () use ($actor, $article, $path): void {
                $article->fill(['cover_path' => $path, 'updated_by' => $actor->id])->save();
            });
        } catch (Throwable $e) {
            $disk->delete($path);

            throw $e;
        }

        if ($old !== null && $old !== $path) {
            $disk->delete($old);
        }

        return $article;
    }

    /**
     * @return array<string, mixed>
     */
    private function textRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.(int) config('shop.article.title_max')],
            'summary' => ['required', 'string', 'max:'.(int) config('shop.article.summary_max')],
            'body' => ['required', 'string', 'max:'.(int) config('shop.article.body_max')],
        ];
    }

    private function authorizePublish(User $actor): void
    {
        if (! $actor->can(self::PUBLISH_PERMISSION)) {
            throw new AuthorizationException('You may not publish articles.');
        }
    }

    private function assertLive(Article $article): void
    {
        if ($article->trashed()) {
            throw new ConflictException('The article is deleted.');
        }
    }
}
