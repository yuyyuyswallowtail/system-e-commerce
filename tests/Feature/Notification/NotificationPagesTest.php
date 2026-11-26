<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Notification\Livewire\NotificationList;
use App\Modules\Notification\Livewire\UnreadBadge;
use App\Modules\Notification\Notifications\OrderPlacedNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\IdentityKit;

function makeNote(User $user, string $title = 'Order placed', bool $read = false): string
{
    $eventUuid = (string) Str::uuid();
    $user->notify(new OrderPlacedNotification($eventUuid, (string) Str::uuid(), ['total_amount' => 100, 'currency' => 'USD']));
    $note = $user->notifications()->where('data', 'like', '%"event_uuid":"'.$eventUuid.'"%')->firstOrFail();
    $note->forceFill(['data' => array_merge($note->data, ['title' => $title]), 'read_at' => $read ? now() : null])->save();

    return (string) $note->id;
}

it('sends guests to login', function () {
    $this->get('/notifications')->assertRedirect(route('login'));
});

it('shows an empty state to a member', function () {
    $this->actingAs(IdentityKit::user('member'))->get('/notifications')->assertOk()->assertSee('No notifications.');
});

it('lists only the own notifications and escapes content', function () {
    $me = IdentityKit::user('member');
    $other = IdentityKit::user('member');
    makeNote($me, '<script>alert(1)</script>');
    makeNote($other, 'Secret of other');

    $this->actingAs($me)->get('/notifications')
        ->assertDontSee('Secret of other')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

it('filters unread only', function () {
    $me = IdentityKit::user('member');
    makeNote($me, 'Unread one');
    makeNote($me, 'Read one', true);

    Livewire::actingAs($me)->test(NotificationList::class)
        ->assertSee('Unread one')->assertSee('Read one')
        ->set('unreadOnly', true)
        ->assertSee('Unread one')->assertDontSee('Read one');
});

it('marks one as read and all as read', function () {
    $me = IdentityKit::user('member');
    $a = makeNote($me);
    makeNote($me);

    $component = Livewire::actingAs($me)->test(NotificationList::class)->call('markRead', $a);
    expect($me->unreadNotifications()->count())->toBe(1);

    $component->call('markAllRead');
    expect($me->unreadNotifications()->count())->toBe(0);
});

it('deletes an own notification', function () {
    $me = IdentityKit::user('member');
    $id = makeNote($me);

    Livewire::actingAs($me)->test(NotificationList::class)->call('delete', $id);

    expect($me->notifications()->count())->toBe(0);
});

it('answers 404 for the notification of another user', function () {
    $me = IdentityKit::user('member');
    $other = IdentityKit::user('member');
    $id = makeNote($other);

    Livewire::actingAs($me)->test(NotificationList::class)->call('markRead', $id)->assertNotFound();
    Livewire::actingAs($me)->test(NotificationList::class)->call('delete', $id)->assertNotFound();

    expect($other->unreadNotifications()->count())->toBe(1);
    expect($other->notifications()->count())->toBe(1);
});

it('shows the unread count in the badge and hides it at zero', function () {
    $me = IdentityKit::user('member');

    Livewire::actingAs($me)->test(UnreadBadge::class)->assertDontSee('data-notification-badge', false);

    makeNote($me);
    makeNote($me);
    makeNote($me, 'x', true);

    Livewire::actingAs($me)->test(UnreadBadge::class)->assertSee('data-notification-badge', false)->assertSee('2');
});

it('polls only when the interval is configured', function () {
    $me = IdentityKit::user('member');

    expect((int) config('notification.poll_seconds'))->toBe(0);
    Livewire::actingAs($me)->test(UnreadBadge::class)->assertDontSee('wire:poll', false);

    config(['notification.poll_seconds' => 30]);
    Livewire::actingAs($me)->test(UnreadBadge::class)->assertSee('wire:poll.30s', false);
});

it('shows the badge in the storefront navbar', function () {
    $me = IdentityKit::user('member');
    makeNote($me);

    $this->actingAs($me)->get('/notifications')->assertSee('data-notification-badge', false);
});

it('adds the menu entry', function () {
    $texts = array_column((array) config('adminlte.menu'), 'text');

    expect($texts)->toContain('Notifications');
});
