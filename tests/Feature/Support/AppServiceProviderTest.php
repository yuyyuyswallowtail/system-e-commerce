<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

it('prevents lazy loading outside production', function () {
    expect(app()->isProduction())->toBeFalse()
        ->and(Model::preventsLazyLoading())->toBeTrue();
});

it('keeps UTC as the default timezone', function () {
    expect(date_default_timezone_get())->toBe('UTC');
});
