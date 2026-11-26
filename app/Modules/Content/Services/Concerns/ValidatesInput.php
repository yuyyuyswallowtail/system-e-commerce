<?php

declare(strict_types=1);

namespace App\Modules\Content\Services\Concerns;

use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\Validator;

trait ValidatesInput
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $fields = [];

            foreach ($validator->errors()->messages() as $field => $messages) {
                $fields[$field] = (string) $messages[0];
            }

            throw new DomainValidationException($fields);
        }

        return $validator->validated();
    }
}
