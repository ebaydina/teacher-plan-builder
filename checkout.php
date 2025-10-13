<?php

use Calendar\Db;
use Stripe\Checkout\Session;
use Stripe\Stripe;

require_once 'Backend/autoload.php';

/** @var Db $db */
try {
    $api = new Calendar\Api($db, false);
} catch (Throwable $e) {
    $message = 'Failure on checkout';
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

$stripeSecretKey = '';
if (defined('STRIPE_SECRET_KEY')) {
    $stripeSecretKey = constant('STRIPE_SECRET_KEY');
}
$host = '';
if (defined('HOST')) {
    $host = constant('HOST');
}
$priceId = @$_GET['priceId'];
$token = @$_GET['token'];

try {
    $user = $api->session(token: $token);
    $customerId = $api->readCustomerId($user['id']);
    if ($customerId === '') {
        $customerId = $api->createStripeUser($user);
    }
    $mode = 'subscription';
    if (isset($_GET['book']) && $_GET['book'] === 'true') {
        $mode = 'payment';
    }

    Stripe::setApiKey($stripeSecretKey);
    header('Content-Type: application/json');
    $YOUR_DOMAIN = $host;

    $checkout_session = Session::create([
        'customer' => $customerId,
        'line_items' => [
            [
                'price' => $priceId,
                'quantity' => 1,
            ]
        ],
        'mode' => $mode,
        'success_url' => $YOUR_DOMAIN . '/index.php',
        'cancel_url' => $YOUR_DOMAIN . '/index.php',
        'customer_update' => ['address' => 'auto'],
        'automatic_tax' => [
            'enabled' => true,
        ],
    ]);
} catch (\Throwable $e) {
    $api->logException(
        $e,
        'Failure buy with Stripe API',
        get_defined_vars()
    );

    throw $e;
}

header("HTTP/1.1 303 See Other");
header("Location: " . $checkout_session->url);