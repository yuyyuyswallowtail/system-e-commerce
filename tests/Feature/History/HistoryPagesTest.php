<?php

declare(strict_types=1);

use App\Modules\History\Livewire\Admin\HistoryIndex;
use App\Modules\History\Livewire\MyHistory;
use Livewire\Livewire;
use Tests\Support\HistoryKit;
use Tests\Support\IdentityKit;

it('sends guests to login', function () {
    $this->get('/me/history')->assertRedirect(route('login'));
    $this->get('/admin/history')->assertRedirect(route('login'));
});

it('lets a member see own history but not the admin page', function () {
    $member = IdentityKit::user('member');

    $this->actingAs($member)->get('/me/history')->assertOk();
    $this->actingAs($member)->get('/admin/history')->assertForbidden();
});

it('lets permitted staff see the admin page', function () {
    $staff = IdentityKit::userWithPermissions(['history.read']);

    $this->actingAs($staff)->get('/admin/history')->assertOk();
});

it('forbids staff without the permission', function () {
    $staff = IdentityKit::userWithPermissions(['orders.read']);

    $this->actingAs($staff)->get('/admin/history')->assertForbidden();
});

it('shows an empty state', function () {
    $member = IdentityKit::user('member');

    $this->actingAs($member)->get('/me/history')->assertSee('No history yet.');
});

it('lists only the events of the logged in user', function () {
    $member = IdentityKit::user('member');
    $other = IdentityKit::user('member');
    $mine = HistoryKit::row(['user_id' => $member->id]);
    $theirs = HistoryKit::row(['user_id' => $other->id]);

    $this->actingAs($member)->get('/me/history')
        ->assertSee($mine->subject_uuid)
        ->assertDontSee($theirs->subject_uuid);
});

it('filters the own list by subject type and action', function () {
    $member = IdentityKit::user('member');
    $order = HistoryKit::row(['user_id' => $member->id, 'subject_type' => 'order', 'action' => 'placed']);
    $payment = HistoryKit::row(['user_id' => $member->id, 'subject_type' => 'payment', 'action' => 'paid']);

    Livewire::actingAs($member)->test(MyHistory::class)
        ->set('filters.subject_type', 'order')
        ->assertSee($order->subject_uuid)
        ->assertDontSee($payment->subject_uuid)
        ->set('filters.subject_type', '')
        ->set('filters.action', 'paid')
        ->assertSee($payment->subject_uuid)
        ->assertDontSee($order->subject_uuid);
});

it('drops unknown filters and caps per page', function () {
    $member = IdentityKit::user('member');

    $component = Livewire::actingAs($member)->test(MyHistory::class)
        ->set('filters.bogus', 'x')
        ->set('perPage', 100000)
        ->assertOk();

    expect($component->get('filters'))->not->toHaveKey('bogus');
    expect($component->instance()->resolvedPerPage())->toBe((int) config('shop.pagination.max'));
});

it('escapes details on the pages', function () {
    $member = IdentityKit::user('member');
    HistoryKit::row(['user_id' => $member->id, 'details' => ['note' => '<script>alert(1)</script>']]);

    $this->actingAs($member)->get('/me/history')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

it('searches the admin list by subject uuid and treats wildcards literally', function () {
    $staff = IdentityKit::userWithPermissions(['history.read']);
    $a = HistoryKit::row();
    $b = HistoryKit::row();

    Livewire::actingAs($staff)->test(HistoryIndex::class)
        ->set('search', $a->subject_uuid)
        ->assertSee($a->subject_uuid)
        ->assertDontSee($b->subject_uuid)
        ->set('search', '%')
        ->assertDontSee($a->subject_uuid);
});

it('filters the admin list by type, action, user and date range', function () {
    $staff = IdentityKit::userWithPermissions(['history.read']);
    $user = IdentityKit::user('member');
    $old = HistoryKit::row(['user_id' => $user->id, 'subject_type' => 'order', 'action' => 'placed', 'occurred_at' => '2026-11-10 12:00:00']);
    $new = HistoryKit::row(['subject_type' => 'payment', 'action' => 'paid', 'occurred_at' => '2026-11-20 12:00:00']);

    Livewire::actingAs($staff)->test(HistoryIndex::class)
        ->set('filters.subject_type', 'payment')
        ->assertSee($new->subject_uuid)->assertDontSee($old->subject_uuid)
        ->set('filters.subject_type', '')
        ->set('filters.action', 'placed')
        ->assertSee($old->subject_uuid)->assertDontSee($new->subject_uuid)
        ->set('filters.action', '')
        ->set('userEmail', $user->email)
        ->assertSee($old->subject_uuid)->assertDontSee($new->subject_uuid)
        ->set('userEmail', '')
        ->set('dateFrom', '2026-11-15')
        ->assertSee($new->subject_uuid)->assertDontSee($old->subject_uuid)
        ->set('dateFrom', '')
        ->set('dateTo', '2026-11-15')
        ->assertSee($old->subject_uuid)->assertDontSee($new->subject_uuid);
});

it('ignores a malformed date and an unknown user email', function () {
    $staff = IdentityKit::userWithPermissions(['history.read']);
    $row = HistoryKit::row();

    Livewire::actingAs($staff)->test(HistoryIndex::class)
        ->set('dateFrom', 'not-a-date')
        ->assertSee($row->subject_uuid)
        ->set('userEmail', 'nobody@example.test')
        ->assertDontSee($row->subject_uuid);
});

it('has no write actions on the admin component', function () {
    foreach (['delete', 'update', 'edit', 'destroy', 'save'] as $method) {
        expect(method_exists(HistoryIndex::class, $method))->toBeFalse();
    }
});

it('refuses the admin component to members', function () {
    Livewire::actingAs(IdentityKit::user('member'))->test(HistoryIndex::class)->assertForbidden();
});
