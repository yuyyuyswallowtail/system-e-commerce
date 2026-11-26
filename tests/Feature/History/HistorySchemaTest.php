<?php

declare(strict_types=1);

use App\Modules\History\Models\HistoryEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\HistoryKit;

it('runs the migration twice without error and has every column', function () {
    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0);
    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0);
    expect(Schema::hasColumns('history_events', [
        'id', 'event_uuid', 'user_id', 'actor_id', 'subject_type', 'subject_uuid', 'action', 'details', 'occurred_at', 'created_at',
    ]))->toBeTrue();
});

it('keeps event_uuid unique', function () {
    $row = HistoryKit::row();

    expect(fn () => HistoryKit::row(['event_uuid' => $row->event_uuid]))->toThrow(QueryException::class);
});

it('stores details as an array and allows null users', function () {
    $row = HistoryKit::row(['details' => ['amount' => 5]]);

    expect($row->fresh()->details)->toEqual(['amount' => 5]);
    expect($row->fresh()->user_id)->toBeNull();
});

it('refuses updates', function () {
    $row = HistoryKit::row();

    expect(fn () => $row->update(['action' => 'paid']))->toThrow(LogicException::class);
    expect(HistoryEvent::query()->whereKey($row->id)->value('action'))->toBe('placed');
});

it('refuses deletes', function () {
    $row = HistoryKit::row();

    expect(fn () => $row->delete())->toThrow(LogicException::class);
    expect(HistoryEvent::query()->count())->toBe(1);
});

it('exposes no write routes for history', function () {
    foreach (Route::getRoutes() as $route) {
        if (Str::contains($route->uri(), 'history')) {
            expect(array_diff($route->methods(), ['GET', 'HEAD']))->toBe([]);
        }
    }
});
