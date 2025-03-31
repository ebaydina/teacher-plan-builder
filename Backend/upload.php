<?php

use Calendar\Db;

require_once 'autoload.php';
/** @var Db $db */
try {
    $api = new Calendar\Api($db, false);
    $api->uploadFile();
} catch (Throwable $e) {
    $exception = var_export($e, true);
    $details = ['EXCEPTION' => $exception];
    $message = 'Failure on upload';
    echo $message;
    if (defined('LOG_PATH')) {
        $parts = [
            constant('LOG_PATH'),
            time()
            . '-'
            . pathinfo(__FILE__, PATHINFO_FILENAME)
            . '.log',
        ];
        $logPath = join(DIRECTORY_SEPARATOR, $parts);
        $isPossible = realpath($logPath) !== false;

        $testMode = 'unknown';
        if ($isPossible && defined('TEST_MODE')) {
            $testMode = constant('TEST_MODE');
        }

        if ($isPossible) {
            $details['TEST_MODE'] = $testMode;

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
}
