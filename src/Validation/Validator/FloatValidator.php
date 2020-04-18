<?php

namespace App\Validation\Validator;

final class FloatValidator implements ValidatorInterface
{
    public function supports(string $type): bool
    {
        return $type === 'float';
    }

    public function validate($data): bool
    {
        return is_numeric($data);
    }
}
