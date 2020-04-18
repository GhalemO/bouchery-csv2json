<?php

namespace App\Validation;

use App\Exception\FileNotFoundException;
use App\Exception\FileNotReadableException;
use App\Validation\Exception\ParsingMetadataException;
use App\Validation\Exception\ValidationException;
use App\Validation\Validator\ValidatorInterface;
use Exception;

/**
 * Handles validation for an array of values by matching each value with a rule set inside a description file
 */
final class ValidationManager
{
    /**
     * Contains configuration metadata loaded from desc file
     *
     * @var array<string,array<string,mixed>>
     */
    protected $configuration = [];

    /**
     * A list of validators
     *
     * @var array<ValidatorInterface>
     */
    protected $validators = [];

    /**
     * Add a validator to the chain of responsibility
     *
     * @param ValidatorInterface $validator
     *
     * @return self
     */
    public function addValidator(ValidatorInterface $validator): self
    {
        $this->validators[] = $validator;

        return $this;
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
    public function loadFromIniSchema(string $fileName): array
    {
        // Validate a file exists
        if (!file_exists($fileName)) {
            throw new FileNotFoundException("The file '$fileName' was not found 😢 !");
        }

        // Opening the file
        $handle = fopen($fileName, 'r');

        if (!$handle) {
            throw new FileNotReadableException("The file '$fileName' could not be open ! Are you sure you have the right to read it ? 🤔");
        }

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

        $this->configuration = $configuration;
        return $this->configuration;
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

    /**
     * Applies validations rules to a CSV extracted row (and even changes some values if needed)
     *
     * @param array<string,mixed> $row ex: ["id" => "12", "name" => "Lior", "date" => "2012-02-02"]
     *
     * @return array<string,mixed>
     */
    public function applySchemaToData(array $row): array
    {
        // Iterating over each field of the row and checking in configuration metadata what are the rules
        foreach ($row as $fieldName => &$value) {
            // If no requirement was given for this field, we go next
            if (empty($this->configuration[$fieldName])) {
                continue;
            }

            // Retrieving requirements for this fieldName
            $requirements = $this->configuration[$fieldName];

            // If value is empty and it is NOT allowed by metadata
            if ($value === '' && empty($requirements['optional'])) {
                throw new ValidationException(sprintf(
                    "The field '%s' can not be empty ! Consider fixing your data or fixing schema with : '%s=?%s'",
                    $fieldName,
                    $fieldName,
                    $requirements['type']
                ));
            }

            // If value is empty but it is allowed by metadata
            if ($value === '' && $requirements['optional'] === true) {
                // Replacing the value by NULL and go next
                $value = null;
                continue;
            }

            // We don't know what validator should take responsibility for the $value
            $typeValidator = null;

            // Let's find out !
            foreach ($this->validators as $validator) {
                if ($validator->supports($requirements['type'])) {
                    $typeValidator = $validator;
                    break;
                }
            }

            // If we did not found any validator for the $value
            if (!$typeValidator) {
                throw new ValidationException(sprintf(
                    "No validator class was found for type '%s' ! You should create a '%s' class to support it or maybe it already exists but was not added to Validators ! 👍",
                    $requirements['type'],
                    'App\\Validation\\Validator\\' . ucfirst($requirements['type']) . 'Validator'
                ));
            }

            // We validate the value and if it does not match with rules .. 💣
            if (!$typeValidator->validate($value)) {
                throw new ValidationException(sprintf(
                    "The field '%s' with value '%s' does not match requirements type '%s' !",
                    $fieldName,
                    $value,
                    $requirements['type']
                ));
            }
        }

        // We return the row because after validation of its values, some of them could have change !
        return $row;
    }
}
