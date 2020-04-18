<?php

namespace App\Validation\Validator;

final class IntegerValidator implements ValidatorInterface
{
    public function supports(string $type): bool
    {
        return $type === 'int' || $type === 'integer';
    }

    public function validate($data): bool
    {
        return is_int($data) || ctype_digit($data);
    }
}
