<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AuditsActions
{
    /**
     * @param  array<string, mixed>  $properties  Never pass passwords, tokens or session ids.
     */
    private function audit(?User $actor, ?Model $subject, string $event, string $description, array $properties = []): void
    {
        $logger = activity('identity')->causedBy($actor)->event($event);

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        if ($properties !== []) {
            $logger->withProperties($properties);
        }

        $logger->log($description);
    }
}
