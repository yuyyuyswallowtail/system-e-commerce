<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Concerns;

use App\Support\Exceptions\DomainValidationException;

trait ReportsDomainErrors
{
    protected function reportDomainErrors(DomainValidationException $exception): void
    {
        foreach ($exception->fields() as $field => $messages) {
            foreach ((array) $messages as $message) {
                $this->addError((string) $field, (string) $message);
            }
        }
    }
}
