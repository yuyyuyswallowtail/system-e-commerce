<?php

declare(strict_types=1);

use App\Support\Sql;

it('escapes LIKE wildcards and the escape character', function () {
    expect(Sql::escapeLike('100%'))->toBe('100\\%')
        ->and(Sql::escapeLike('a_b'))->toBe('a\\_b')
        ->and(Sql::escapeLike('a\\b'))->toBe('a\\\\b')
        ->and(Sql::escapeLike('plain'))->toBe('plain')
        ->and(Sql::escapeLike(''))->toBe('');
});
