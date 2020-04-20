<?php

namespace App\Validation;

use App\Validation\Exception\LoaderNotFoundException;
use App\Validation\Exception\ValidationException;
use App\Validation\Schema\DescLoaderInterface;
use App\Validation\Validator\ValidatorInterface;

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
     * A list of description file loaders
     *
     * @var array<DescLoaderInterface>
     */
    protected $loaders = [];

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
     * Returns the loaded (or empty) configuration as an array
     *
     * @return array<string,array<string,mixed>>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }


    /**
     * Add a description file loader to the chain of responsibility
     *
     * @param DescLoaderInterface $loader
     *
     * @return self
     */
    public function addLoader(DescLoaderInterface $loader): self
    {
        $this->loaders[] = $loader;

        return $this;
    }

    /**
     * Loads a description schema 
     *
     * @param string $fileName
     * 
     * @throws LoaderNotFoundException if no loader was found for the file's extension
     *
     * @return void
     */
    public function loadSchemaFromFile(string $fileName): void
    {
        // Guessing which loader has to be used in this case
        // Ex: for .bini file, we will use BoucheryDescLoader
        $loader = $this->findMatchingLoader($fileName);

        // If no loader was found
        if (!$loader) {
            throw new LoaderNotFoundException(sprintf(
                'No loader was found to analyse "%s" !',
                $fileName
            ));
        }

        // We load the configuration out of the file
        $this->configuration = $loader->load($fileName);
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

    /**
     * Loops over all the available loaders and returns the first one who supports the given filename
     *
     * @param string $fileName
     *
     * @return DescLoaderInterface|null
     */
    protected function findMatchingLoader(string $fileName): ?DescLoaderInterface
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($fileName)) {
                return $loader;
            }
        }

        return null;
    }
}
