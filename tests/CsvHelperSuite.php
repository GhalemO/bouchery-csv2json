<?php

use App\Csv\CsvFileHelper;
use App\Csv\Exception\CsvFileException;

describe('CsvFileHelper : testing the class CsvFileHelper', function () {
    // Setup
    $csvHelper = new CsvFileHelper();

    $pathClassic = createCacheDataFile(
        <<<DATA
id,name,date
10,Lior,2012-02-02
12,Magali,2015-03-03
DATA
    );

    $pathEntourloupe = createCacheDataFile(
        <<<DATA
"id,name";"date,time"
"10,Lior";"2012-02-02,14:15:50"
DATA,
        "entourloupe.csv"
    );

    $pathToEmpty = createCacheDataFile('', 'empty.csv');

    it('can guess delimiter in a string', function () use ($csvHelper) {
        $delimiter = $csvHelper->findDelimiterInString('id,"name and age",date');

        return assertEquals($delimiter, ',');
    });

    it('can guess delimiter in tricky string', function () use ($csvHelper) {
        $delimiter = $csvHelper->findDelimiterInString('id;"date,test";"time,test";age;"age,birth,email,data"', [',', ';']);

        return assertEquals($delimiter, ';');
    });

    it('can extract delimiters from simple file', function () use ($pathClassic, $csvHelper) {
        $classicCsvHandle = fopen($pathClassic, 'r');

        $classicDelimiter = $csvHelper->detectCSVFileDelimiter($classicCsvHandle);

        fclose($classicCsvHandle);

        return assertEquals($classicDelimiter, ',');
    });

    it('can extract delimiters from tricky file', function () use ($pathEntourloupe, $csvHelper) {
        $entourloupeCsvHandle = fopen($pathEntourloupe, 'r');
        $entourloupeDelimiter = $csvHelper->detectCSVFileDelimiter($entourloupeCsvHandle);
        fclose($entourloupeCsvHandle);

        return assertEquals($entourloupeDelimiter, ';');
    });

    it('can extract headers from csv file', function () use ($pathClassic, $csvHelper) {
        $expectedHeaders = ['id', 'name', 'date'];

        $csvHandle = fopen($pathClassic, 'r');

        $actualHeaders = $csvHelper->extractHeadersFromCSVFile($csvHandle);

        fclose($csvHandle);

        return assertSameArrays($expectedHeaders, $actualHeaders);
    });

    it('can extract data properly from a CSV filestream', function () use ($pathClassic, $csvHelper) {
        $expectedResult = [
            [
                "id" => "10",
                "name" => "Lior",
                "date" => "2012-02-02"
            ],
            [
                "id" => "12",
                "name" => "Magali",
                "date" => "2015-03-03"
            ]
        ];

        $csvHandle = fopen($pathClassic, 'r');
        $actualResult = $csvHelper->extractDataFromCSVFile($csvHandle);

        return assertSameArrays($expectedResult, $actualResult);
    });

    it('will throw a CsvFileException::class if CSV file can\'t be found', function () use ($csvHelper) {
        return assertCodeWillThrowException(function () use ($csvHelper) {
            $handle = @fopen('trash/path/for/nothing', 'r');
            $csvHelper->extractDataFromCSVFile($handle);
        }, CsvFileException::class);
    });

    it('will throw a CsvFileException::class if CSV file is empty', function () use ($csvHelper, $pathToEmpty) {
        return assertCodeWillThrowException(function () use ($csvHelper, $pathToEmpty) {
            $handle = fopen($pathToEmpty, 'r');
            $csvHelper->extractDataFromCSVFile($handle);
        }, CsvFileException::class);
    });

    describe("Just an example of a nested suite :)", function () {
        it('is an other test inside a suite inside an other suite !', function () {
            return true;
        });
    });
});
