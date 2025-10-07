<?php

use Calendar\Api;

require_once 'autoload.php';
session_start();
new Api($db);