<?php

try {
    require_once 'Backend/upload.php';
} catch (Throwable $e) {
    $message = 'Failure on upload';
    echo $message;

    include_once 'write-log.php';
    if(function_exists('writeLog')){

        $exception = var_export($e, true);
        $details = ['EXCEPTION' => $exception];
        $filename = pathinfo(__FILE__, PATHINFO_FILENAME);

        writeLog($message, $details, $filename);
    }

    exit;
}
