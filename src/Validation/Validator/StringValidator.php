<?php

namespace App\Validation\Validator;

final class StringValidator implements ValidatorInterface
{
    public function supports(string $type): bool
    {
        return $type === 'string';
    }

    public function validate($data): bool
    {
        return is_string($data) && mb_strlen($data) > 0;
    }
}
