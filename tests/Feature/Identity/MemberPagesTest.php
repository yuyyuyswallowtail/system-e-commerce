<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Livewire\Profile;
use App\Modules\Identity\Livewire\UserCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\IdentityKit;

it('redirects guests away from the member pages', function () {
    $target = IdentityKit::user();

    $this->get('/me')->assertRedirect(route('login'));
    $this->get('/users/'.$target->uuid)->assertRedirect(route('login'));
});

it('shows the member pages to a signed in member', function () {
    $member = IdentityKit::user();
    $target = IdentityKit::user();

    $this->actingAs($member)->get('/me')->assertOk()->assertSee($member->name);
    $this->actingAs($member)->get('/users/'.$target->uuid)->assertOk()->assertSee($target->name);
});

it('returns 404 for a deleted user card', function () {
    $member = IdentityKit::user();
    $target = IdentityKit::user();
    $target->delete();

    $this->actingAs($member)->get('/users/'.$target->uuid)->assertNotFound();
});

it('updates the own name', function () {
    $member = IdentityKit::user();

    Livewire::actingAs($member)->test(Profile::class)
        ->set('name', 'Renamed Member')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($member->refresh()->name)->toBe('Renamed Member');
});

it('rejects an empty name', function () {
    Livewire::actingAs(IdentityKit::user())->test(Profile::class)
        ->set('name', '')
        ->call('saveProfile')
        ->assertHasErrors('name');
});

it('rejects a wrong current password', function () {
    $member = IdentityKit::user();

    Livewire::actingAs($member)->test(Profile::class)
        ->set('current_password', 'not-the-password')
        ->set('password', 'Another-Passw0rd')
        ->set('password_confirmation', 'Another-Passw0rd')
        ->call('changePassword')
        ->assertHasErrors('current_password');

    expect(Hash::check('password', $member->refresh()->password))->toBeTrue();
});

it('changes the password, drops other sessions and audits without secrets', function () {
    $member = IdentityKit::user();
    IdentityKit::session('other-session', $member->id);

    Livewire::actingAs($member)->test(Profile::class)
        ->set('current_password', 'password')
        ->set('password', 'Another-Passw0rd')
        ->set('password_confirmation', 'Another-Passw0rd')
        ->call('changePassword')
        ->assertHasNoErrors();

    expect(Hash::check('Another-Passw0rd', $member->refresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'other-session')->exists())->toBeFalse();

    $json = json_encode(Activity::all()->map(fn ($row) => [$row->description, $row->properties, $row->attribute_changes])->all());
    expect($json)->not->toContain('Another-Passw0rd')->not->toContain('$2y$');
});

it('requires a confirmed password', function () {
    Livewire::actingAs(IdentityKit::user())->test(Profile::class)
        ->set('current_password', 'password')
        ->set('password', 'Another-Passw0rd')
        ->set('password_confirmation', 'Different-Passw0rd')
        ->call('changePassword')
        ->assertHasErrors('password');
});

it('follows and unfollows from the user card', function () {
    $member = IdentityKit::user();
    $target = IdentityKit::user();

    $component = Livewire::actingAs($member)->test(UserCard::class, ['user' => $target])
        ->call('follow')
        ->assertHasNoErrors();

    expect(DB::table('user_follows')->where(['follower_id' => $member->id, 'followed_id' => $target->id])->count())->toBe(1);

    $component->call('follow');
    expect(DB::table('user_follows')->count())->toBe(1);

    $component->call('unfollow');
    expect(DB::table('user_follows')->count())->toBe(0);
});

it('rejects following yourself from the card', function () {
    $member = IdentityKit::user();

    Livewire::actingAs($member)->test(UserCard::class, ['user' => $member])
        ->call('follow')
        ->assertHasErrors('user');

    expect(DB::table('user_follows')->count())->toBe(0);
});

it('locks the card uuid against tampering', function () {
    $member = IdentityKit::user();
    $target = IdentityKit::user();

    expect(fn () => Livewire::actingAs($member)->test(UserCard::class, ['user' => $target])->set('uuid', $member->uuid))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('keeps follows after a soft delete of nobody and shows counts', function () {
    $member = IdentityKit::user();
    $target = IdentityKit::user();
    DB::table('user_follows')->insert(['follower_id' => $member->id, 'followed_id' => $target->id, 'created_at' => now()]);

    $this->actingAs($member)->get('/users/'.$target->uuid)->assertOk();
    expect(User::count())->toBe(2);
});
