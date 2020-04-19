<?php

use App\Validation\Exception\ParsingMetadataException;
use App\Validation\Schema\BoucheryDescLoader;
use App\Validation\Schema\XmlDescLoader;
use App\Validation\ValidationManager;
use App\Validation\Validator\BooleanValidator;
use App\Validation\Validator\StringValidator;

describe('Validation processes tests suite', function () {
    describe('ValidationManager', function () {
        // Setup :
        $manager = new ValidationManager();
        $boucheryLoader = new BoucheryDescLoader();
        $xmlLoader = new XmlDescLoader();

        $manager
            ->addLoader($boucheryLoader)
            ->addLoader($xmlLoader);

        it('should analyse a well formatted .bini file', function () use ($manager) {
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

            $manager->loadSchemaFromFile($descFile);

            return assertSameArrays($expectedMetadata, $manager->getConfiguration());
        });

        it('should throw an exception if a .bini file is badly formatted', function () use ($manager) {
            $badDescFile = createCacheDataFile(
                <<<DESC
        # Comment line
        name
        id?integer
        DESC,
                'bad-schema.bini'
            );

            return assertCodeWillThrowException(function () use ($manager, $badDescFile) {
                $manager->loadSchemaFromFile($badDescFile);
            });
        });
    });

    describe('StringValidator', function () {
        $validator = new StringValidator();

        $otherTypes = ['int', 'integer', 'bool', 'boolean', 'date', 'datetime', 'time'];
        $otherValues = [12, 12.5, true, false];

        it('should support "string" type', function () use ($validator) {
            return assertEquals(true, $validator->supports("string"));
        });

        it('should not support any of ' . json_encode($otherTypes) . ' type', function () use ($validator, $otherTypes) {
            foreach ($otherTypes as $type) {
                if ($validator->supports($type)) {
                    return false;
                }
            }

            return true;
        });

        it('should validate a real string', function () use ($validator) {
            return assertEquals(true, $validator->validate('Hello World !'));
        });

        it('should not validate any value of ' . json_encode($otherValues), function () use ($validator, $otherValues) {
            foreach ($otherValues as $value) {
                if ($validator->validate($value)) {
                    return false;
                }
            }

            return true;
        });
    });


    describe('BooleanValidator', function () {
        $validator = new BooleanValidator();

        $otherTypes = ['int', 'integer', 'string', 'date', 'datetime', 'time'];
        $booleanValues = [
            true, 'true', 1, '1', 'on', 'yes',
            false, 'false', 0, '0', 'off', 'no'
        ];
        $otherValues = [12, 12.5, 'Hello World'];

        it('should support "bool" type', function () use ($validator) {
            return assertEquals(true, $validator->supports("bool"));
        });

        it('should support "boolean" type', function () use ($validator) {
            return assertEquals(true, $validator->supports("boolean"));
        });

        it('should not support any of ' . json_encode($otherTypes) . ' type', function () use ($validator, $otherTypes) {
            foreach ($otherTypes as $type) {
                if ($validator->supports($type)) {
                    return false;
                }
            }

            return true;
        });

        it('should validate any of ' . json_encode($booleanValues), function () use ($validator, $booleanValues) {
            foreach ($booleanValues as $value) {
                if (!$validator->validate($value)) {
                    alert("Did not validate correct value $value");
                    return false;
                }
            }

            return true;
        });

        it('should not validate any value of ' . json_encode($otherValues), function () use ($validator, $otherValues) {
            foreach ($otherValues as $value) {
                if (true === $validator->validate($value)) {
                    alert("Did validate well $value");
                    return false;
                }
            }

            return true;
        });
    });
});
