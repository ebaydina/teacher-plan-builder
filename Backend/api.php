<?php

require_once 'autoload.php';
session_start();
new Calendar\Api($db);
