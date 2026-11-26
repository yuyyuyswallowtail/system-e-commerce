<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Livewire\HasTableQuery;

class TqTable
{
    use HasTableQuery;

    public int $pageResets = 0;

    public function resetPage(): void
    {
        $this->pageResets++;
    }

    protected function tableKey(): string
    {
        return 'tq_items';
    }
}
