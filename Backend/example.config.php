<?php
define('DEV', 0);
define('VERSION', '1.0.4');
define('DB_HOST', 'localhost');
define('DB_USER', 'username');
define('DB_PASSWORD', '***');
define('DB_NAME', 'database_name');

define('HOST', 'https://site.domen/');
define('HOST_PATH', dirname(__DIR__) . '/');
define('UPLOADS_PATH', dirname(__DIR__) . '/uploads/');
define('UPLOADS_LINK', HOST . 'uploads/');
define('CONTENT_PATH', dirname(__DIR__) . '/content/');
define('CALENDAR_IMAGES_PATH', dirname(__DIR__) . '/img/calendar/');

define('SESSION_EXPIRES', 30 * 3600);
define('VERIFY_LINK_EXPIRES', 3600);
define('CONFIRM_CODE_EXPIRES', 3600);

define('TITLE', 'Teacher Plan Builder');

$db = new Calendar\Db(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if(DEV){
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
}