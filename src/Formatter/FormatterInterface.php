<?php

namespace App\Formatter;

interface FormatterInterface
{
    public function supports(string $format): bool;

    /**
     * Format the data structure into a string
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $options
     *
     * @return string
     */
    public function format(array $data, array $options = []): string;
}
