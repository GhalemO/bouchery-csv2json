<?php

namespace App\Formatter;

final class JsonFormatter implements FormatterInterface
{
    public function supports(string $format): bool
    {
        return $format === "json";
    }

    /**
     * Format the data structure into JSON
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $options
     *
     * @return string
     */
    public function format(array $data, array $options = []): string
    {
        if (!empty($options['pretty'])) {
            return json_encode($data, JSON_PRETTY_PRINT);
        }

        return json_encode($data);
    }
}
