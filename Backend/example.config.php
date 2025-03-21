<?php

define('DEV', 0);
#define('DEV', 1);
if (DEV) {
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
}

define('DB_HOST', 'localhost');
define('DB_USER', 'u718471842_tpb');
define('DB_PASSWORD', '***');
define('DB_NAME', 'u718471842_tpb');
try {
    $db = new Calendar\Db(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
} catch (Throwable $e) {
    echo 'Failure on establish connection to database';
}

define('TEST_MODE', 0);
#define('TEST_MODE', 1);
if (TEST_MODE) {
    define(
        'STRIPE_SECRET_KEY',
        'sk_test_51Qv7DyPbukN8lAgtdfUbUkUn8NxAJyaML9ec21oTKAAEbdL73K5tcTvHsu9WnNuBuCkU8bNrjBW0ZyjzWt3GOjhI00iOyN7bfx',
    );
    define(
        'ANNUAL_SUBSCRIPTION_INDIVIDUAL',
        'price_1R1joFPbukN8lAgtMy9hfPa8',
    );
    define(
        'MONTHLY_SUBSCRIPTION_INDIVIDUAL',
        'price_1Qv7QEPbukN8lAgtvYbUur6U',
    );
    define(
        'ANNUAL_SUBSCRIPTION_SCHOOL',
        'price_1R1jnNPbukN8lAgtiSYKzi3n',
    );
    define(
        'MONTHLY_SUBSCRIPTION_SCHOOL',
        'price_1Qv7R2PbukN8lAgtvjZOJrx2',
    );
    define(
        'BOOK_WITH_SUBSCRIPTION',
        'price_1Qv7PLPbukN8lAgtmL2ZeG2k',
    );
    define(
        'FULL_PRICE_OF_BOOK',
        'price_1R1jvHPbukN8lAgtoLzTzs1n',
    );
}
if (!TEST_MODE) {
    define(
        'STRIPE_SECRET_KEY',
        '***',
    );
    define(
        'ANNUAL_SUBSCRIPTION_INDIVIDUAL',
        '***',
    );
    define(
        'MONTHLY_SUBSCRIPTION_INDIVIDUAL',
        '***',
    );
    define(
        'ANNUAL_SUBSCRIPTION_SCHOOL',
        '***',
    );
    define(
        'MONTHLY_SUBSCRIPTION_SCHOOL',
        '***',
    );
    define(
        'BOOK_WITH_SUBSCRIPTION',
        '***',
    );
    define(
        'FULL_PRICE_OF_BOOK',
        '***',
    );
}

define('VERSION', '1.0.4');
#define('HOST', 'https://teacherplanbuilder.com/');
define('HOST', 'http://teacher.localhost/');
define('HOST_PATH', dirname(__DIR__) . '/');
define('LOG_PATH', dirname(__DIR__) . '/log/');
define('UPLOADS_PATH', dirname(__DIR__) . '/uploads/');
define('UPLOADS_LINK', HOST . 'uploads/');
define('CONTENT_PATH', dirname(__DIR__) . '/content/');
define('CALENDAR_IMAGES_PATH', dirname(__DIR__) . '/img/calendar/');

define('SESSION_EXPIRES', 30 * 3600);
define('VERIFY_LINK_EXPIRES', 3600);
define('CONFIRM_CODE_EXPIRES', 3600);

define('TITLE', 'Teacher Plan Builder');
