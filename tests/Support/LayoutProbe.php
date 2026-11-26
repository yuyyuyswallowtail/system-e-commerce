<?php

declare(strict_types=1);

namespace Tests\Support;

use Livewire\Component;

class LayoutProbe extends Component
{
    public function render(): string
    {
        return <<<'HTML'
        <div id="layout-probe">layout probe content</div>
        HTML;
    }
}
