<?php

namespace App\Validation\Validator;

/**
 * The interface that every Validator should implement !
 */
interface ValidatorInterface
{
    /**
     * Allows clients to know if a validator is able to validate a value based on her type !
     *
     * @param string $type Ex: "string", "datetime" or "time"
     *
     * @return boolean
     */
    public function supports(string $type): bool;

    /**
     * Validates a given value ($data) against some rules
     *
     * @param mixed $data
     *
     * @return boolean
     */
    public function validate($data): bool;
}
