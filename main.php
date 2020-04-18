<?php

require_once __DIR__ . '/tests/lib/cli.php';

if (empty($argv[1])) {
    alert("You have to specify a command to the script, you can ask for 'csv2json' or 'unit-test'");
    exit();
}

$command = $argv[1];

if (!in_array($command, ['csv2json', 'unit-test'])) {
    alert(sprintf("Command '%s' not found, available commands are 'csv2json' or 'unit-test'", $command));
    exit();
}

array_splice($argv, 1, 1);

if ($command === 'csv2json') {
    require __DIR__ . '/app.php';
    exit();
}

require __DIR__ . '/test.php';
