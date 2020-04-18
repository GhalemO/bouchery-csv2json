<?php

/**
 * Number of tabulations to add before each output
 * 
 * @var int
 */
$tabsCount = 0;

/**
 * Increments global $tabsCount
 *
 * @return void
 */
function incrementTabsCount(): void
{
    global $tabsCount;
    $tabsCount++;
}

/**
 * Decrements global $tabsCount
 *
 * @return void
 */
function decrementTabsCount(): void
{
    global $tabsCount;
    $tabsCount--;
}

/**
 * Maps $tabsCount to a string containing two spaces for each tab needed
 *
 * @return string
 */
function getTabs(): string
{
    global $tabsCount;

    $tabs =  "";

    for ($i = 0; $i < $tabsCount; $i++) {
        $tabs .=  "  ";
    }

    return $tabs;
}


/**
 * Outputs a header 
 * 
 * Exemple with string "My header":
 * My header
 * ---------
 *
 * @param string $message
 *
 * @return void
 */
function head(string $message): void
{
    echo newLine() . $message;
    echo newLine();
    for ($i = 0; $i < mb_strlen($message); $i++) {
        echo "-";
    }
}

/**
 * Outputs a footer
 * 
 * Exemple for string "My footer":
 * ---------
 * My footer
 *
 * @param string $message
 *
 * @return void
 */
function footer(string $message): void
{
    echo newLine();
    for ($i = 0; $i < mb_strlen($message); $i++) {
        echo "-";
    }
    echo newLine() . $message;
}

/**
 * Outputs a simple message
 *
 * @param string|array<string> $message
 *
 * @return void
 */
function info($message): void
{
    if (is_array($message)) {
        echo implode(newLine(), $message);
        return;
    }

    echo newLine() . $message;
}

/**
 * Outputs an alert (red background)
 *
 * @param string|array<string> $message
 *
 * @return void
 */
function alert($message): void
{
    if (is_array($message)) {
        foreach ($message as $line) {
            echo newLine() . bgRed($line);
        }
        return;
    }
    echo newLine() . bgRed($message);
}

function red(string $message): string
{
    return "\e[0;31m$message\e[0m";
}

function bgRed(string $message): string
{

    return "\e[1;37;41m$message\e[0m";
}

function bgGreen(string $message): string
{
    return "\e[1;37;42m$message\e[0m";
}

function newLine(): string
{
    return "\n" . getTabs();
}
