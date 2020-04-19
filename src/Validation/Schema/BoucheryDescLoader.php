<?php

namespace App\Validation\Schema;

use App\Exception\FileNotFoundException;
use App\Exception\FileNotReadableException;
use App\Validation\Exception\ParsingMetadataException;
use Exception;

final class BoucheryDescLoader implements DescLoaderInterface
{
    public function supports(string $fileName): bool
    {
        $components = pathinfo($fileName);

        return $components['extension'] === 'bini';
    }

    /**
     * Load configuration metadata from a description file
     * 
     * Description file looks like a INI file :
     * name = string
     * id= ?int
     * 
     * Will be transformed into an array like :
     * [
     *  "name" => ["type" => "string", "optional" => false],
     *  "id" => ["type" => "int", "optional" => true]
     * ]
     * 
     * The configuration metadata was designed to be simpler to analyse when it comes to validate a value
     * against description file's rules
     *
     * @param string $fileName
     *
     * @return array<string,array<string,mixed>>
     */
    public function load(string  $fileName): array
    {
        // Validate a file exists
        if (!file_exists($fileName)) {
            throw new FileNotFoundException("The file '$fileName' was not found or can not be read 😢 !");
        }

        // Opening the file
        $handle = fopen($fileName, 'r');

        // Loading the configuration metadata
        $configuration = [];

        // Keeping track of line index in the description file
        $lineIndex = 0;

        // Foreach line in the description file
        while ($line = fgets($handle)) {
            $lineIndex++;

            // If it begins with a '#' => it is a comment, go next
            if ($this->isComment($line)) {
                continue;
            }

            // We will try to analyse a line and if it fails, throw an exception with more descriptive message
            try {
                // Retrieve a line analytics
                // Ex: id=?int will give ['fieldName' => 'id', 'metadata' => ['type' => 'int', 'optional' => true]]
                $data = $this->analyseDescriptionLine($line);

                // Retrieving fieldName and metadata
                $fieldName = $data['fieldName'];
                $metadata = $data['metadata'];

                // Adding all informations to $configuration
                $configuration[$fieldName] = $metadata;
            } catch (Exception $e) {
                throw new ParsingMetadataException(sprintf(
                    '%s on line %d in file %s',
                    $e->getMessage(),
                    $lineIndex,
                    $fileName
                ));
            }
        }

        return $configuration;
    }

    /**
     * Analyse a string from a description file and generate matching metadata
     *
     * @param string $line
     *
     * @return array<string,mixed>
     */
    protected function analyseDescriptionLine(string $line): array
    {
        // If the line is not a comment, we can read it as INI string 
        // ex: "id=?int" will become ["id" => "?int"]
        $data = parse_ini_string($line);

        // If we could not read the line, throws a parsing error
        if (!$data) {
            throw new ParsingMetadataException(sprintf(
                "Line '%s' is not well formatted",
                $line
            ));
        }

        // ex: "id"
        $fieldName = key($data);
        // ex: "?int"
        $value = $data[$fieldName];

        return ['fieldName' => $fieldName, 'metadata' => $this->generateMetadata($value)];
    }

    /**
     * Generate readable and easy to manipulate metadata out of a simple string
     * Ex: "?integer" becomes ["type" => "integer", "optional" => true]
     * Ex: "string" becomes ["type" => "string", "optional" => false]
     * 
     * @param string $value
     *
     * @return array<string,string|bool>
     */
    protected function generateMetadata(string $value): array
    {
        // Handling the case of optionnal data
        // Ex: "?int" will become ['type' => 'int', 'optional' => true]
        // Ex: "string" will become ['type' => 'string', 'optional' => false]
        return [
            'type' => str_replace('?', '', $value),
            'optional' => $this->isOptionnal($value)
        ];
    }

    /**
     * Verifies if a line of the description file is a comment or not
     *
     * @param string $line
     *
     * @return boolean
     */
    protected function isComment(string $line): bool
    {
        return strpos(trim($line), '#') === 0;
    }

    /**
     * Checks if a value is optionnal
     *
     * @param string $value
     *
     * @return boolean
     */
    protected function isOptionnal(string $value): bool
    {
        return strpos($value, '?') === 0;
    }
}
