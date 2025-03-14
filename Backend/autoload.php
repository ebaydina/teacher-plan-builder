<?php

spl_autoload_register(function ($className) {
    $fileName = 'Backend/' . str_replace("\\", "/", $className) . '.php';
    if (file_exists($fileName)) {
        require_once $fileName;
    }
});
require_once "config.php";
require_once "Modules/require.php";
