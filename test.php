<?php

require_once __DIR__ . '/tests/lib/functions.php';
require_once __DIR__ . '/autoload.php';

$suites = scandir(__DIR__ . '/tests/');


if (!empty($argv[1])) {
    if (!file_exists($argv[1])) {
        alert(sprintf("File %s could not be found !", $argv[1]));
        return;
    }

    head("Now running suite {$argv[1]} :");
    require_once $argv[1];
    info("");
    done();
    return;
}

foreach ($suites as $file) {
    if (endsWith($file, "Suite.php")) {
        head("Now running suite $file :");
        require_once __DIR__ . '/tests/' . $file;
        info("");
    }
}

done();

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

// $csvHelper->detectCSVFileDelimiter($handle, DELIMITERS)
// $csvHelper->extractDataFromCSVFile($handle, $fields)
// $csvHelper->extractHeadersFromCSVFile($handle, $reset)
// $csvHelper->findDelimiterInString($string, DELIMITERS)
