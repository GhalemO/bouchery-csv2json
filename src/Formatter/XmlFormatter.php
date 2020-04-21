<?php

namespace App\Formatter;

use DOMDocument;
use SimpleXMLElement;

final class XmlFormatter implements FormatterInterface
{
    public function supports(string $format): bool
    {
        return $format === 'xml';
    }

    /**
     * Format the data structure into XML
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $options
     *
     * @return string
     */
    public function format(array $data, array $options = []): string
    {
        $rootNode = new SimpleXMLElement('<export/>');
        $this->transform($data, $rootNode, $options);

        $document = new DOMDocument();
        $document->loadXML($rootNode->asXML());
        $document->formatOutput = !empty($options['pretty']);

        return $document->saveXML();
    }

    /**
     * Format the data structure into XML
     *
     * @param array<string,mixed> $data
     * @param SimpleXMLElement $rootNode
     * @param array<string,mixed> $options
     *
     */
    protected function transform($data, SimpleXMLElement $rootNode, array $options = []): void
    {
        if (empty($options['aggregate'])) {
            foreach ($data as $row) {
                $node = $rootNode->addChild('item');

                foreach ($row as $field => $value) {
                    $node->addAttribute($field, $value);
                }
            }
            return;
        }

        foreach ($data as $aggregateRoot => $others) {
            $node = $rootNode->addChild($aggregateRoot);

            foreach ($others as $nonAggregatedData) {
                $field = key($nonAggregatedData);
                $value = $nonAggregatedData[$field];
                $node->addChild($field, $value);
            }
        }
    }
}
