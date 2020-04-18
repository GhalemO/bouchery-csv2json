<?php

namespace App\Validation\Validator;

final class DateTimeValidator implements ValidatorInterface
{
    public function supports(string $type): bool
    {
        return $type === 'datetime';
    }

    public function validate($data): bool
    {
        return is_string($data) && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (0[0-9]|1[0-9]|2[0-3]):(0[0-9]|1[0-9]|2[0-9]|3[0-1]):([0-5][0-9])$/", $data);
    }
}
