<?php

namespace App\CommandLine;

use App\CommandLine\Exception\CommandLineMissingArgumentException;
use App\CommandLine\Exception\CommandLineUnknownOptionException;
use App\CommandLine\Exception\CommandLineMissingValueException;

final class CommandLineHelper
{
    private const FILENAME_ARGUMENT_INDEX = 1;

    /**
     * @var array<string>
     */
    protected array $arguments = [];

    /**
     * You have to provide an array of arguments (like $argv for instance)
     * 
     * @param array<string> $arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    /**
     * Returns the CSV file name given in the command line args
     *
     * @return string
     */
    public function getCsvFileName(): string
    {
        if (
            !isset($this->arguments[self::FILENAME_ARGUMENT_INDEX]) ||
            strpos($this->arguments[self::FILENAME_ARGUMENT_INDEX], '--') === 0
        ) {
            throw new CommandLineMissingArgumentException("You did not provide a CSV file path ! Reminder : app.php <path_to_file.csv> [options]");
        }

        return $this->arguments[self::FILENAME_ARGUMENT_INDEX];
    }

    /**
     * @param array<string,bool> $availableOptions
     * @return array<string,string>
     */
    public function extractOptionsFromArgs(array $availableOptions = []): array
    {
        // Assuming user gave no options
        $options = [];

        // We scroll all arguments (excluding 0 (the script name) and 1 (the csv file))
        for ($i = 2; $i < count($this->arguments); $i++) {
            // Retrieving option name without -- (ex: '--aggregate' becomes 'aggregate')
            $optionName = str_replace('--', '', $this->arguments[$i]);

            // Extracting available options names (ex: ['aggregate' => true, 'pretty' => false] becomes ['aggragate', 'pretty'])
            $availableOptionsNames =  array_keys($availableOptions);

            // If an option was given but it is not available in this script
            if (!in_array($optionName, $availableOptionsNames)) {
                throw new CommandLineUnknownOptionException(sprintf(
                    "The given option '%s' can't be used in this script ! Only %s can be used 😭",
                    $optionName,
                    implode(', ', array_map(fn ($opt) => "--$opt", $availableOptionsNames))
                ));
            }

            // If current option does not need a value
            if (isset($availableOptions[$optionName]) && false === $availableOptions[$optionName]) {
                $options[$optionName] = true;
                continue;
            }

            // Finding the value of the option (the next argument in command line)
            if (!isset($this->arguments[$i + 1])) {
                throw new CommandLineMissingValueException(sprintf("You gave no value for '--%s' option but an option it is needed !", $optionName));
            }
            // By the way, we increment $i so the next iteration will jump off the next argument (which is in fact a value ..)
            $value = $this->arguments[++$i];

            // If the value for this option is the next option, then we missed the value !
            if (strpos($value, '--') === 0) {
                throw new CommandLineMissingValueException(sprintf("You gave '%s' as a value for '--%s' option ! We think you forgot to give the real value 😭", $value, $optionName));
            }

            // Adding the option and it's value
            $options[$optionName] = $value;
        }

        return $options;
    }
}
