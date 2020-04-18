<?php

require_once __DIR__ . '/cli.php';

/**
 * Global count for tests executed
 * 
 * @var int
 */
$testsCount = 0;

/**
 * Global count for failed tests
 * 
 * @var int
 */
$failures = 0;


function incrementTestsCount(): void
{
    global $testsCount;
    $testsCount++;
}

function incrementFailures(): void
{
    global $failures;
    $failures++;
}

/**
 * Brings you an array with results : 
 * 
 * ['tests' => 40, 'failures' => 10, 'success' => 30]
 *
 * @return array<string,int>
 */
function getTestsResults(): array
{
    global $failures, $testsCount;

    $success = $testsCount - $failures;

    return [
        'tests' => $testsCount,
        'failures' => $failures,
        'success' => $success
    ];
}

/**
 * Helps you for the creation of a test file
 *
 * @param string $content
 * @param string $filename
 *
 * @return string The complete file path
 */
function createCacheDataFile(string $content, string $filename = null): string
{
    if (!$filename) {
        $filename = uniqid() . '.tmp';
    }

    $path = __DIR__ . '/../cache/' . $filename;
    file_put_contents($path, $content);
    return $path;
}

/**
 * Allows you to describe a testsuite
 *
 * @param string $description
 * @param callable $fn The tests suite (with a lots of it(..) inside !)
 *
 * @return void
 */
function describe(string $description, callable $fn): void
{
    info("");
    head("✨ " . $description);

    incrementTabsCount();

    call_user_func($fn);

    decrementTabsCount();
}

/**
 * Allows you to perform a test
 *
 * @param string $description The description of the action we test
 * @param callable $fn A callable that will return a boolean
 *
 * @return void
 */
function it(string $description, callable $fn): void
{
    incrementTestsCount();

    try {
        $result = call_user_func($fn);
    } catch (Exception $e) {
        $result = $e->getMessage();
    }


    if (!is_bool($result) && !is_string($result)) {
        $caller = debug_backtrace();
        $message = [
            "You have to return a boolean or a string from a 'it()' test case callback !",
            "You can also return a string if your want to fail with a message !",
            "Please update your it('$description') callback around line " . (string) ($caller[0]['line'] - 1) . " !"
        ];
        info("❌ It $description");
        alert($message);
        incrementFailures();
        return;
    }

    if (is_bool($result)) {
        info(($result ? "✅" : "❌") . " It $description");
        return;
    }

    incrementFailures();
    info("❌ It $description (" . red($result) . ")");
}

/**
 * Checks if 2 values are STRICTLY equal
 * 
 * @param mixed $x
 * @param mixed $y
 *
 * @return boolean|string
 */
function assertEquals($x, $y)
{
    $errorMessage = sprintf("Could not assert that %s is equal to %s !", json_encode($x), json_encode($y));
    if ($x !== $y) {
        return $errorMessage;
    }

    return true;
}

/**
 * Tests if 2 arrays are exactly the sames (keys and values included)
 * 
 * @param array<mixed> $arr1
 * @param array<mixed> $arr2
 *
 * @return boolean|string
 */
function assertSameArrays(array $arr1, array $arr2)
{
    $json1 = json_encode($arr1);
    $json2 = json_encode($arr2);

    if ($json1 !== $json2) {
        return sprintf(
            "Could not assert that %s is same as %s !",
            $json1,
            $json2
        );
    }

    return true;
}

/**
 * Checks if a code will throw an exception, you can also specify the Exception class it should throw
 *
 * @param callable $fn The code that should throw an exception
 * @param string $exceptionClass The exception class the code should throw (default : Exception::class)
 *
 * @return boolean|string
 */
function assertCodeWillThrowException(callable $fn, string $exceptionClass = Exception::class)
{
    try {
        call_user_func($fn);

        return "This code did not trigger any Exception !";
    } catch (Exception $e) {
        if ($e instanceof $exceptionClass) {
            return true;
        }

        return sprintf(
            "\nThis code did not trigger the specified exception (%s), instance of '%s' instead\nMessage : %s",
            $exceptionClass,
            get_class($e),
            $e->getMessage()
        );
    }
}


/**
 * Outputs a summary for tests overall
 *
 * @return void
 */
function done(): void
{
    clearCacheFolder();

    $results = getTestsResults();

    info("");
    if (!empty($results['failures'])) {
        footer(sprintf('▶ %d tests were executed !', $results['tests']));
        alert(sprintf('▶ %d tests failed !', $results['failures']));
        info("");
        return;
    }

    info(bgGreen(sprintf('▶ All tests (%d) succeeded !', $results['success'])));
    info("");
}

/**
 * Clears all the files in the cache folder and returns the number of deleted files
 *
 * @return integer
 */
function clearCacheFolder(): int
{
    $deletedFiles = 0;
    $cachePath = __DIR__ . '/../cache';

    foreach (scandir($cachePath) as $file) {
        if ($file !== '.' && $file !== '..') {
            unlink($cachePath . '/' . $file);
            $deletedFiles++;
        }
    }

    return $deletedFiles;
}


/**
 * Re-creates a $argv style array out of a string
 * Ex: app.php myfile.csv --fields "name,date" becomes ["app.php", "myfile.csv", "--fields", "name,date"]
 *
 * @param string $command
 *
 * @return array<string>
 */
function getArgumentsForCommand(string $command): array
{
    return array_map(fn ($str) => str_replace('"', '', $str), explode(' ', $command));
}
