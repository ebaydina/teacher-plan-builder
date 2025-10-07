<?php

use Calendar\Db;

require_once 'autoload.php';

$api = new Calendar\Api($db, false);
$api->uploadFile();
