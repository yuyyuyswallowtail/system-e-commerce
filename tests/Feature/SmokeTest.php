<?php

declare(strict_types=1);

it('boots the application', function () {
    expect(app()->isBooted())->toBeTrue()
        ->and(app()->environment())->toBe('testing');
});

it('uses UTC as the default timezone', function () {
    expect(date_default_timezone_get())->toBe('UTC')
        ->and(config('app.timezone'))->toBe('UTC');
});
