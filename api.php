<?php

try {
    require_once 'Backend/api.php';
} catch (Throwable $e) {
    $message = 'Failure on API call';
    echo $message;

    include_once __DIR__
        . DIRECTORY_SEPARATOR
        . 'write-log.php';
    if(function_exists('writeLog')){

        $exception = var_export($e, true);
        $details = ['EXCEPTION' => $exception];
        $filename = pathinfo(__FILE__, PATHINFO_FILENAME);

        writeLog($message, $details, $filename);
    }

    exit;
}
