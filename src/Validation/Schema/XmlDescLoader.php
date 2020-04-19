<?php

namespace App\Validation\Schema;

use App\Exception\FileNotFoundException;
use App\Validation\Exception\ParsingMetadataException;

final class XmlDescLoader implements DescLoaderInterface
{
    public function supports(string $fileName): bool
    {
        $components = pathinfo($fileName);

        return $components['extension'] === 'xml';
    }

    public function load(string  $fileName): array
    {

        // Validate a file exists
        if (!file_exists($fileName)) {
            throw new FileNotFoundException("The file '$fileName' was not found or it can not be read, are your sure you have the needed rights ? 😢");
        }

        // Opening the file
        $xml = @simplexml_load_file($fileName);

        if (!$xml) {
            throw new ParsingMetadataException("The file '$fileName' does not contain valid XML and could not be read !");
        }


        $configuration = [];

        foreach ($xml->xpath('//field') as $field) {
            $fieldData = [];

            foreach ($field->attributes() as $attr => $value) {
                $value = (string) $value;

                $fieldData[$attr] = $this->getRealValue($value);
            }

            $configuration[$fieldData['id']] = [
                'type' => $fieldData['type'],
                'optional' => !empty($fieldData['optional'])
            ];
        }

        return $configuration;
    }

    /**
     * Return a real scalar value for a given string value
     * Ex: "true" becomes true
     * Ex: "string" stays "string"
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function getRealValue(string $value)
    {
        if ($value === "true") {
            return true;
        }

        if ($value === "false") {
            return false;
        }

        return $value;
    }
}
