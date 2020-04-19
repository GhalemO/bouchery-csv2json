<?php

namespace App\Validation\Schema;

interface DescLoaderInterface
{

    /**
     * Loads a description file and returns metadata in an array
     *
     * @param string $fileName
     *
     * @return array<string,array<string,mixed>>
     */
    public function load(string $fileName): array;

    /**
     * Returns true if the file type (from the given fileName) is supported by this loader
     *
     * @param string $fileName
     *
     * @return boolean
     */
    public function supports(string $fileName): bool;
}
