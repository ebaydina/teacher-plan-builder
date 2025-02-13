<?php
require_once 'autoload.php';
$api = new Calendar\Api($db, false);
$api->uploadFile();
