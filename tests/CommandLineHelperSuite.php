<?php

use App\CommandLine\CommandLineHelper;
use App\CommandLine\Exception\CommandLineMissingArgumentException;
use App\CommandLine\Exception\CommandLineUnknownOptionException;
use App\CommandLine\Exception\CommandLineMissingValueException;

describe('CommandLineHelper Suite !', function () {

    it('accepts an option that requires value if it has a value specified', function () {
        $arguments = getArgumentsForCommand('app.php file.csv --xxxx test');

        $options = (new CommandLineHelper($arguments))
            ->extractOptionsFromArgs(['xxxx' => true]);

        $expectedOptions = ['xxxx' => 'test'];

        return assertSameArrays($expectedOptions, $options);
    });

    it('accepts an option that does not require a value if it has no value specified', function () {
        $arguments = getArgumentsForCommand('app.php file.csv --xxxx"');

        $options = (new CommandLineHelper($arguments))->extractOptionsFromArgs(['xxxx' => false]);

        return assertSameArrays(['xxxx' => true], $options);
    });

    it('throws a CommandLineMissingValueException::class if an option that requires a value does not have a value', function () {
        return assertCodeWillThrowException(function () {
            $arguments = getArgumentsForCommand('app.php file.csv --xxxx');

            (new CommandLineHelper($arguments))
                ->extractOptionsFromArgs(['xxxx' => true]);
        }, CommandLineMissingValueException::class);
    });

    it('throws a CommandLineMissingValueException::class if an option that requires a value is directly followed by an other option', function () {
        return assertCodeWillThrowException(function () {
            $arguments = getArgumentsForCommand('app.php file.csv --xxxx --yyyy "name"');

            (new CommandLineHelper($arguments))
                ->extractOptionsFromArgs(['xxxx' => true, 'yyyy' => true]);
        }, CommandLineMissingValueException::class);
    });

    it('throws a CommandLineUnknownOptionException::class if an option which was not expected is passed to the script', function () {
        return assertCodeWillThrowException(function () {
            $arguments = getArgumentsForCommand('app.php file.csv --xxxx --yyyy');

            (new CommandLineHelper($arguments))->extractOptionsFromArgs(['xxxx' => false]);
        }, CommandLineUnknownOptionException::class);
    });

    it('gives us the right name for the csv file passed to the script', function () {
        $arguments = getArgumentsForCommand('app.php file.csv');

        return assertEquals('file.csv', (new CommandLineHelper($arguments))->getCsvFileName());
    });

    it('throws a CommandLineArgumentMissingException::class if no csv file path was given', function () {
        return assertCodeWillThrowException(function () {
            $arguments = getArgumentsForCommand('app.php --option');

            (new CommandLineHelper($arguments))->getCsvFileName();
        }, CommandLineMissingArgumentException::class);
    });
});
