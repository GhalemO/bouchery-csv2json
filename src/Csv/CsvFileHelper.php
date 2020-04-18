<?php

namespace App\Csv;

use App\Csv\Exception\CsvFileException;
use Exception;

final class CsvFileHelper
{
    /**
     * Represents available delimiters for a given CSV file
     */
    private const DELIMITERS = [',', ';', "\t", '|', ':'];

    /**
     * Extracts the first line of the CSV in an array of headers
     * 
     * @param resource $csvFileHandle The resource
     * @param bool $withReset Comes back to the initial position after reading first line
     * @return array<string> $headers
     */
    public function extractHeadersFromCSVFile($csvFileHandle, bool $withReset = false): array
    {
        // Validating the resource is valid before reading from it
        $this->throwExceptionIfHandleIsInvalid($csvFileHandle);

        // Guessing CSV delimiter
        $delimiter = $this->detectCSVFileDelimiter($csvFileHandle);

        // Keeping actual position in memory
        $actualLine = ftell($csvFileHandle);

        // Going back to the begining of the file
        fseek($csvFileHandle, 0);

        // Extracting first row as an array
        $headers = fgetcsv($csvFileHandle, 0, $delimiter);

        // If a reset was asked, we move the resource to the position where it was before we read first line
        if ($withReset) {
            fseek($csvFileHandle, $actualLine);
        }

        // If we could not extract headers
        if (!is_array($headers)) {
            throw new CsvFileException("We could not read the first line of the file as a CSV row ! Are you sure it is well formatted ? Or maybe the file is empty 🤔 ?");
        }

        return $headers;
    }

    /**
     * Extract rows from a CSV and returns them as a structured array
     * 
     * @param resource $csvFileHandle The resource of the CSV file
     * @param array<string> $fields An array of fields we want to extract
     *
     * @return array<int,array<string,mixed>> Structured CSV rows
     */
    public function extractDataFromCSVFile($csvFileHandle, array $fields = []): array
    {
        // Validating the file handle is a real resource
        $this->throwExceptionIfHandleIsInvalid($csvFileHandle);

        // Guessing delimiter for the CSV File
        $delimiter = $this->detectCSVFileDelimiter($csvFileHandle);

        // Extracting headers as an array
        $headers = $this->extractHeadersFromCSVFile($csvFileHandle);

        $data = [];

        // If no field were indicated, we will take all headers into account
        if (count($fields) === 0) {
            $fields = [...$headers];
        }

        // Iterating through all CSV file rows
        while ($line = fgetcsv($csvFileHandle, 0, $delimiter)) {
            $row = [];
            // Iterating through each value of the row
            for ($i = 0; $i < count($line); $i++) {
                // Guessing the value field name with headers
                $header = $headers[$i];

                // If this field is not wanted in the result, we ignore it
                if (!in_array($header, $fields)) {
                    continue;
                }

                // Otherwise, we add it to our data structure
                $row[$header] = $line[$i];
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param resource $csvFileHandle
     * @param array<string> $delimiters
     *
     * @return string
     */
    public function detectCSVFileDelimiter($csvFileHandle, array $delimiters = self::DELIMITERS): string
    {

        // Validate the resource is valid and we can read out of it
        $this->throwExceptionIfHandleIsInvalid($csvFileHandle);

        // Keeping memory of the actual position in the file
        $actualLine = ftell($csvFileHandle);

        // Getting the next line to study it
        $line = fgets($csvFileHandle);

        // Repositionning on the initial position
        fseek($csvFileHandle, $actualLine);

        if ($line) {
            return $this->findDelimiterInString($line, $delimiters);
        } else {
            return key($delimiters);
        }
    }

    /**
     * Returns the most promising candidate of delimiter with a shitty strategy which works fine in the end :D
     * 
     * @param string $str A CSV row
     * @param array<string> $delimiters An array of delimiters candidates
     */
    public function findDelimiterInString(string $str, array $delimiters = self::DELIMITERS): string
    {
        // We will count for each delimiter how many fields we can split and we will take the maximum
        $delimitersWithCount = [];

        // Defining number of extracted fields for each delimiter
        foreach ($delimiters as $delimiter) {
            // Tried to do it with str_getcsv but this is buggy and does not take into account the enclosure ...
            //     $delimitersWithCount[$delimiter] = count(str_getcsv($str, $delimiter));

            // So here is a little algorithm to cut by myself a csv string into chunks
            $inEnclosure = false;
            $chunks = [];
            $currentChunk = '';
            for ($i = 0; $i < mb_strlen($str); $i++) {
                $char = $str[$i];

                if ($char === '"') {
                    if ($inEnclosure) {
                        $inEnclosure = false;
                        $chunks[] = $currentChunk;
                        $currentChunk = "";
                        continue;
                    }

                    $inEnclosure = true;
                    continue;
                }

                if ($char === $delimiter && !$inEnclosure) {
                    $chunks[] = $currentChunk;
                    $currentChunk = "";
                    continue;
                }

                $currentChunk .= $char;

                if ($i === mb_strlen($str) - 1) {
                    $chunks[] = $currentChunk;
                }
            }
            $delimitersWithCount[$delimiter] = count($chunks);
        }

        // We return the delimiter which has a maximum of fields extracted
        return (string) array_search(max($delimitersWithCount), $delimitersWithCount);
    }


    /**
     * @param resource $handle The file stream you want to validate
     *
     * @throws Exception if the resource is not a valid one
     */
    private function throwExceptionIfHandleIsInvalid($handle): void
    {
        if (!is_resource($handle)) {
            throw new CsvFileException("Provided parameter is not a valid resource 😭");
        }
    }
}
