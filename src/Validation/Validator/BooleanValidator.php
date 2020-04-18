<?php

namespace App\Validation\Validator;

final class BooleanValidator implements ValidatorInterface
{
    public function supports(string $type): bool
    {
        return $type === 'bool' || $type === 'boolean';
    }

    public function validate($data): bool
    {
        $acceptableValues = [
            'true', true, 1, '1', 'on', 'yes',
            'false', false, 0, '0', 'off', 'no'
        ];

        return in_array($data, $acceptableValues, true);
    }
}
