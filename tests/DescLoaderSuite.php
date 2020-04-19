<?php

use App\Validation\Exception\ParsingMetadataException;
use App\Validation\Schema\BoucheryDescLoader;
use App\Validation\Schema\XmlDescLoader;

describe('Bouchery Description Loader', function () {

    $loader = new BoucheryDescLoader();

    it('should support any .bini file', function () use ($loader) {
        $exampleFile = createCacheDataFile(<<<DESC
        # any content
        DESC, 'example-desc.bini');
        return assertEquals(true, $loader->supports($exampleFile));
    });

    it('should not support a file which is not .bini', function () use ($loader) {
        $exampleFile = createCacheDataFile(<<<DESC
        # any content
        DESC, 'example-desc.xml');

        return assertEquals(false, $loader->supports($exampleFile));
    });

    it('should analyse a well formatted .bini file', function () use ($loader) {
        $descFile = createCacheDataFile(
            <<<DESC
    # Comment line
    name = string
    id=?integer
    # Other comment
    date=datetime
    DESC,
            'validation-schema.bini'
        );

        $expectedMetadata = [
            'name' => ['type' => 'string', 'optional' => false],
            'id' => ['type' => 'integer', 'optional' => true],
            'date' => ['type' => 'datetime', 'optional' => false]
        ];

        return assertSameArrays($expectedMetadata, $loader->load($descFile));
    });

    it('should throw an exception if a .bini file is badly formatted', function () use ($loader) {
        $badDescFile = createCacheDataFile(
            <<<DESC
    # Comment line
    name
    id?integer
    DESC,
            'bad-schema.bini'
        );

        return assertCodeWillThrowException(function () use ($loader, $badDescFile) {
            $loader->load($badDescFile);
        }, ParsingMetadataException::class);
    });
});


describe('Xml Description Loader', function () {

    $loader = new XmlDescLoader();

    it('should support any .xml file', function () use ($loader) {
        $exampleFile = createCacheDataFile(<<<DESC
        <any-content/>
        DESC, 'example-desc.xml');
        return assertEquals(true, $loader->supports($exampleFile));
    });

    it('should not support a file which is not .xml', function () use ($loader) {
        $exampleFile = createCacheDataFile(<<<DESC
        # any content
        DESC, 'example-desc.ini');

        return assertEquals(false, $loader->supports($exampleFile));
    });

    it('should analyse a well formatted .xml file', function () use ($loader) {
        $descFile = createCacheDataFile(
            <<<DESC
    <schema>
        <field id="name" type="string" />
        <field id="id" type="integer" optional="true" />
        <!-- Other comment -->
        <field id="date" type="datetime" />
    </schema>
    DESC,
            'validation-schema.xml'
        );

        $expectedMetadata = [
            'name' => ['type' => 'string', 'optional' => false],
            'id' => ['type' => 'integer', 'optional' => true],
            'date' => ['type' => 'datetime', 'optional' => false]
        ];

        return assertSameArrays($expectedMetadata, $loader->load($descFile));
    });

    it('should throw an exception if a .xml file is badly formatted', function () use ($loader) {
        $badDescFile = createCacheDataFile(
            <<<DESC
    # Comment line
    name
    id?integer
    DESC,
            'bad-schema.bini'
        );

        return assertCodeWillThrowException(function () use ($loader, $badDescFile) {
            $loader->load($badDescFile);
        }, ParsingMetadataException::class);
    });
});
