<?php

use Stripe\Checkout\Session;
use Stripe\Stripe;

require_once 'Backend/autoload.php';

$stripeSecretKey = '';
if (defined('STRIPE_SECRET_KEY')) {
    $stripeSecretKey = constant('STRIPE_SECRET_KEY');
}
$host = '';
if (defined('HOST')) {
    $host = constant('HOST');
}
$priceId = $_GET['priceId'];
$token = $_GET['token'];

$api = new Calendar\Api($db, false);
$user = $api->session(token: $token);
$customerId = '';
if (is_array($user)) {
    $customerId = $user['stripe-customer-id'];
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

header("HTTP/1.1 303 See Other");
header("Location: " . $checkout_session->url);