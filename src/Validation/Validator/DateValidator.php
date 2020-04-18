<?php

namespace App\Validation\Validator;

final class DateValidator implements ValidatorInterface
{
    public function supports(string $type): bool
    {
        return $type === "date";
    }

    public function validate($data): bool
    {
        return is_string($data) && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $data);
    }
}
