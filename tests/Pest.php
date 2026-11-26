<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

pest()->use(RefreshDatabase::class)->in('Feature/Identity', 'Unit/Identity', 'Feature/Catalog', 'Unit/Catalog', 'Feature/Inventory', 'Unit/Inventory', 'Feature/Cart', 'Unit/Cart', 'Feature/Payment', 'Unit/Payment', 'Feature/Order', 'Unit/Order', 'Feature/History', 'Unit/History', 'Feature/Notification', 'Unit/Notification', 'Feature/Content', 'Unit/Content');
