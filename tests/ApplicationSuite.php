<?php

use App\Application;
use App\CommandLine\CommandLineHelper;
use App\Csv\CsvFileHelper;
use App\Exception\CsvInvalidValueException;
use App\Exception\FileNotFoundException;
use App\Formatter\JsonFormatter;
use App\Validation\Schema\BoucheryDescLoader;
use App\Validation\Schema\XmlDescLoader;
use App\Validation\ValidationManager;
use App\Validation\Validator\DateValidator;
use App\Validation\Validator\IntegerValidator;
use App\Validation\Validator\StringValidator;

describe('Application tests suite with a valid CSV', function () {
    createCacheDataFile(
        <<<DATA
    id,name,date
    1,Lior,2012-01-02
    2,Magali,2012-02-02
    DATA,
        'application.csv'
    );

    $expectedData = [
        [
            'id' => "1",
            "name" => 'Lior',
            'date' => '2012-01-02'
        ],
        [
            'id' => "2",
            "name" => 'Magali',
            'date' => '2012-02-02'
        ]
    ];

    // setup
    $boucheryDescLoader = new BoucheryDescLoader();
    $xmlDescLoader = new XmlDescLoader();
    $validationManager = (new ValidationManager())->addLoader($boucheryDescLoader)->addLoader($xmlDescLoader);

    $csvHelper = new CsvFileHelper();

    it('should print the expected JSON', function () use ($validationManager, $csvHelper, $expectedData) {
        $commandLineHelper = new CommandLineHelper(getArgumentsForCommand('app.php tests/cache/application.csv'));

        $app = (new Application($csvHelper, $commandLineHelper, $validationManager))->addFormatter(new JsonFormatter);

        $json = $app->run();

        $expectedJson = json_encode($expectedData);

        return assertEquals($expectedJson, $json);
    });

    it('should take into account the --pretty option', function () use ($validationManager, $csvHelper, $expectedData) {
        $commandLineHelper = new CommandLineHelper(getArgumentsForCommand('app.php tests/cache/application.csv --pretty'));

        $app = (new Application($csvHelper, $commandLineHelper, $validationManager))->addFormatter(new JsonFormatter);

        $json = $app->run();

        $expectedJson = json_encode($expectedData, JSON_PRETTY_PRINT);

        return assertEquals($expectedJson, $json);
    });

    it('should aggregate data based on the --aggregate option', function () use ($validationManager, $csvHelper) {
        $commandLineHelper = new CommandLineHelper(getArgumentsForCommand('app.php tests/cache/application.csv --aggregate "id"'));

        $app = (new Application($csvHelper, $commandLineHelper, $validationManager))->addFormatter(new JsonFormatter);

        $json = $app->run();
        $expectedData = [
            '1' => [
                ['name' => 'Lior', 'date' => '2012-01-02']
            ],
            '2' => [
                ['name' => 'Magali', 'date' => '2012-02-02']
            ]
        ];

        $expectedJson = json_encode($expectedData);

        return assertEquals($expectedJson, $json);
    });


    it('should extract only fields indicated with --fields option', function () use ($validationManager, $csvHelper) {
        $commandLineHelper = new CommandLineHelper(getArgumentsForCommand('app.php tests/cache/application.csv --fields "id,name"'));

        $app = (new Application($csvHelper, $commandLineHelper, $validationManager))->addFormatter(new JsonFormatter);

        $json = $app->run();
        $expectedData = [
            ['id' => '1', 'name' => 'Lior'],
            ['id' => '2', 'name' => 'Magali']
        ];

        $expectedJson = json_encode($expectedData);

        return assertEquals($expectedJson, $json);
    });

    $descFilePath = createCacheDataFile(<<<DESC
    # Description file
    id=integer
    name=string
    date=date
    DESC, 'schema.bini');

    it('should accept a metadata file with --desc option', function () use ($validationManager, $csvHelper, $expectedData, $descFilePath) {


        $commandLineHelper = new CommandLineHelper(getArgumentsForCommand("app.php tests/cache/application.csv --desc " . $descFilePath));

        $validationManager->addValidator(new IntegerValidator)
            ->addValidator(new StringValidator)
            ->addValidator(new DateValidator);

        $app = (new Application($csvHelper, $commandLineHelper, $validationManager))->addFormatter(new JsonFormatter);

        $json = $app->run();

        $expectedJson = json_encode($expectedData);

        return assertEquals($expectedJson, $json);
    });

    it('should throw an exception if a validator is missing but needed in --desc file', function () use ($csvHelper, $descFilePath, $boucheryDescLoader) {
        $commandLineHelper = new CommandLineHelper(getArgumentsForCommand("app.php tests/cache/application.csv --desc " . $descFilePath));

        $customValidationManager = new ValidationManager(); // We do not add validators to test its behavior withou
        // any validator!
        $customValidationManager->addLoader($boucheryDescLoader);

        return assertCodeWillThrowException(function () use ($csvHelper, $commandLineHelper, $customValidationManager) {
            $app = (new Application($csvHelper, $commandLineHelper, $customValidationManager))->addFormatter(new JsonFormatter);

            $app->run(); // Should throw an exception since it does not have validation !
        }, CsvInvalidValueException::class);
    });

    it('should throw an exception if the file given was not found', function () use ($validationManager, $csvHelper) {
        $commandLineHelper = new CommandLineHelper(getArgumentsForCommand('app.php notfound.csv'));

        $app = new Application($csvHelper, $commandLineHelper, $validationManager);

        return assertCodeWillThrowException(function () use ($app) {
            $app->run();
        }, FileNotFoundException::class);
    });
});
