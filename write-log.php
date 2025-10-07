<?php

function writeLog(string $message, array $details, string $module)
{
    $isPossible = false;
    if (defined('LOG_PATH')) {
        $isPossible = realpath(LOG_PATH) !== false;
    }

    $testMode = 'unknown';
    if ($isPossible && defined('TEST_MODE')) {
        $testMode = TEST_MODE;
    }
    if ($isPossible && !isset($details['TEST_MODE'])) {
        $details['TEST_MODE'] = $testMode;
    }

    if ($isPossible) {
        $parts = [
            LOG_PATH,
            time()
            . '-'
            . $module
            . '.log',
        ];
        $logPath = join(DIRECTORY_SEPARATOR, $parts);

        file_put_contents(
            $logPath,
            date(DATE_ATOM, time())
            . ': '
            . $message
            . ', context: '
            . json_encode(
                $details,
                JSON_NUMERIC_CHECK
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
            ),
            FILE_APPEND,
        );
    }

}