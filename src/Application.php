<?php

namespace App;

use App\CommandLine\CommandLineHelper;
use App\Csv\CsvFileHelper;
use App\Exception\AggregationException;
use App\Exception\CsvInvalidValueException;
use App\Exception\FileNotFoundException;
use App\Exception\FormatterNotFoundException;
use App\Formatter\FormatterInterface;
use App\Validation\Exception\ValidationException;
use App\Validation\ValidationManager;

final class Application
{
    protected CsvFileHelper $csvHelper;
    protected CommandLineHelper $commandLineHelper;
    protected ValidationManager $validator;

    /**
     * @var array<FormatterInterface>
     */
    protected array $formatters = [];

    public function __construct(CsvFileHelper $csvHelper, CommandLineHelper $commandLineHelper, ValidationManager $validator)
    {
        $this->csvHelper = $csvHelper;
        $this->commandLineHelper = $commandLineHelper;
        $this->validator = $validator;
    }

    public function addFormatter(FormatterInterface $formatter): self
    {
        $this->formatters[] = $formatter;

        return $this;
    }

    /**
     * Runs the extraction from the CSV file
     *
     * @return string the JSON formatted string (pretty or not, here I come, you can't hide)
     */
    public function run(): string
    {
        // Retrieving CSV filename from command line
        $fileName = $this->commandLineHelper->getCsvFileName();

        if (!file_exists($fileName)) {
            throw new FileNotFoundException("No file was found located in '$fileName' or can not be read ! Are you sure about the given path ? 🤔");
        }

        // Retrieving options from command line, if an option is marked as true, it means that the options REQUIRES 
        // a value
        $options = $this->commandLineHelper->extractOptionsFromArgs([
            'fields' => true,
            'aggregate' => true,
            'pretty' => false,
            'desc' => true,
            'format' => true
        ]);

        // Opening CSV file and validating it
        $csvFile = fopen($fileName, 'r');

        // Guessing which fields will be extracted
        $fields = $this->guessWantedFieldsForExtraction($csvFile, $options);

        // We extract CSV rows as arrays, including only fields that we want
        $data = $this->csvHelper->extractDataFromCSVFile($csvFile, $fields);

        // If a schema file was provided in options
        if (!empty($options['desc'])) {
            // We load the schema file in the validator engine
            $this->validator->loadSchemaFromFile($options['desc']);

            // We validate data and retrieve new formatted data (ex: empty optionnal fields become `NULL`)
            $data = $this->validateRows($data);
        }

        // Aggregation of data only if 'aggregate' option was found
        if (!empty($options['aggregate'])) {
            // Retrieve the aggregate field (ex: "name")
            $aggregateField = $options['aggregate'];

            // If aggregate field is not in the extracted fields (ex: you ask for "name", but you asked only "id, date" fields)
            if (!in_array($aggregateField, $fields)) {
                throw new AggregationException(sprintf(
                    "Aggregation is impossible with '%s' which is not part of extracted fields (%s) 🤔",
                    $aggregateField,
                    json_encode($fields)
                ));
            }

            // Retrieving aggregated data
            $data = $this->getAggregatedData($data, $aggregateField);
        }

        // Returning the formatted string
        return $this->format($data, $options);
    }

    /**
     * Format the data structure into the desired format
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $options
     *
     * @return string
     */
    protected function format(array $data, array $options = []): string
    {
        // Guessing format type (default: 'json')
        $format = $options['format'] ?? 'json';

        // Guessing formatter :
        $formatter = $this->getFormatter($format);

        if (!$formatter) {
            throw new FormatterNotFoundException("No formatter found for format '$format' !");
        }

        return $formatter->format($data, $options);
    }

    /**
     * Finds the good formatter and returns it (or null if we found nothing 😢)
     *
     * @param string $format
     *
     * @return FormatterInterface|null
     */
    protected function getFormatter(string $format): ?FormatterInterface
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($format)) {
                return $formatter;
            }
        }

        return null;
    }

    /**
     * Validates each row from a CSV extracted array
     *
     * @param array<int,array<mixed>> $data
     *
     * @return array<int,array<mixed>>
     */
    protected function validateRows(array $data): array
    {
        $csvLineIndex = 0;

        try {
            foreach ($data as $csvLineIndex => &$row) {
                $row = $this->validator->applySchemaToData($row);
            }

            return $data;
        } catch (ValidationException $e) {
            throw new CsvInvalidValueException(sprintf(
                "On line %d of your CSV data : %s !",
                $csvLineIndex + 1, // We give index + 1 because in the CSV we have the headers line
                $e->getMessage()
            ));
        }
    }

    /**
     * Guesses an array of fields to be extracted from the CSV file
     *
     * @param resource $csvFile
     * @param array<string,mixed> $options
     *
     * @return array<int,string>
     */
    protected function guessWantedFieldsForExtraction($csvFile, array $options = []): array
    {
        // Retrieving headers from CSV File
        $headers = $this->csvHelper->extractHeadersFromCSVFile($csvFile);

        // Guessing fields filtering :
        // By default we want to extract all fields (so it is the same as headers of the file) 
        $fields = [...$headers];

        // But if there is an option 'fields'
        if (!empty($options['fields'])) {
            // We check what delimiter was used for the 'fields' option value
            $optionDelimiter = $this->csvHelper->findDelimiterInString($options['fields']);
            // We extract fields as an array
            $fields = str_getcsv($options['fields'], $optionDelimiter);
            // We get rid of unwanted spaces ('  id  ' becomes 'id')
            $fields = array_map(fn ($field) => trim($field), $fields);
        }

        return $fields;
    }

    /**
     * Serializes an array in JSON format
     * 
     * TODO: Extract into a Formatter class with a FormatterInterface to enhance SRP and DIP
     *
     * @param array<int,array<string,mixed>> $data
     * @param boolean $pretty
     *
     * @return string
     */
    protected function formatDataIntoJson(array $data, bool $pretty = false): string
    {
        if ($pretty) {
            return json_encode($data, JSON_PRETTY_PRINT);
        }

        return json_encode($data);
    }

    /**
     * Aggregates flat arrays into a more complex structure based on an aggregate field
     *
     * @param array<int,array<string,mixed>> $data
     * @param string $aggregateField
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    protected function getAggregatedData(array $data, string $aggregateField): array
    {
        // Begins with an empty array
        $aggregatedData = [];

        foreach ($data as $row) {
            // Finding the value of aggregate field for this row (ex: "foo")
            $aggregatedValue = $row[$aggregateField];

            // Creating an entry for "foo" in the aggregated array
            if (!isset($aggregatedData[$aggregatedValue])) {
                $aggregatedData[$aggregatedValue] = [];
            }

            // Extracting others values from the row
            $others = [];

            foreach ($row as $field => $value) {
                if ($field === $aggregateField) {
                    continue;
                }

                $others[$field] = $value;
            }

            // Filling all others value as a new row of aggregated value
            $aggregatedData[$aggregatedValue][] = $others;
        }

        return $aggregatedData;
    }
}
