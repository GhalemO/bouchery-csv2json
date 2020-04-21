<?php

use App\Application;
use App\CommandLine\CommandLineHelper;
use App\Csv\CsvFileHelper;
use App\Formatter\JsonFormatter;
use App\Formatter\XmlFormatter;
use App\Validation\Schema\BoucheryDescLoader;
use App\Validation\Schema\XmlDescLoader;
use App\Validation\ValidationManager;
use App\Validation\Validator\BooleanValidator;
use App\Validation\Validator\DateTimeValidator;
use App\Validation\Validator\DateValidator;
use App\Validation\Validator\FloatValidator;
use App\Validation\Validator\IntegerValidator;
use App\Validation\Validator\StringValidator;
use App\Validation\Validator\TimeValidator;

require __DIR__ . '/autoload.php';

$commandLine = new CommandLineHelper($argv);
$csvHelper = new CsvFileHelper();
$validator = new ValidationManager();

$validator
    ->addValidator(new StringValidator)
    ->addValidator(new DateValidator)
    ->addValidator(new IntegerValidator)
    ->addValidator(new FloatValidator)
    ->addValidator(new BooleanValidator)
    ->addValidator(new DateTimeValidator)
    ->addValidator(new TimeValidator)
    ->addLoader(new BoucheryDescLoader)
    ->addLoader(new XmlDescLoader);

$app = (new Application($csvHelper, $commandLine, $validator))->addFormatter(new JsonFormatter)->addFormatter(new XmlFormatter);

try {
    $json = $app->run();

    echo $json;
} catch (\Exception $e) {
    $message = sprintf('An error occured : %s', $e->getMessage());
    echo "\e[1;37;41m$message\e[0m\n";
}
