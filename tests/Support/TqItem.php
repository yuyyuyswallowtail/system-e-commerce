<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;

class TqItem extends Model
{
    public $timestamps = false;

    protected $table = 'tq_items';

    protected $guarded = [];
}
