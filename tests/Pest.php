<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

pest()->use(RefreshDatabase::class)->in('Feature/Identity', 'Unit/Identity', 'Feature/Catalog', 'Unit/Catalog', 'Feature/Inventory', 'Unit/Inventory');
