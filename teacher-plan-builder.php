<?php

require_once 'Backend/autoload.php';

use Calendar\Db;
use Calendar\Functions;

session_start();
/** @var Db $db */
try {
    $api = new Calendar\Api($db, false);
} catch (Throwable $e) {
    $message = 'Failure on render index page';
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

if (!isset($_COOKIE['csrf_token'])) {
    $_COOKIE['csrf_token'] = Functions::csrfToken();
    setcookie('csrf_token', $_COOKIE['csrf_token']);
}
if (isset($_GET['verify'])) {
    $_SESSION['verify'] = $_GET['verify'];
    Functions::redirect('/', true);
}
$verifyResult = false;
if (isset($_SESSION['verify'])) {
    $api->param('code', $_SESSION['verify']);
    $verifyResult = $api->apiVerify();
    if (!is_array($verifyResult)) {
        $verifyResult = [
            'success' => $verifyResult
        ];
    }
    unset($_SESSION['verify']);
}

$version = '1.0.0';
if (defined('VERSION')) {
    $version = constant('VERSION');
}
$dev = 0;
if (defined('DEV')) {
    $dev = constant('DEV');
}
if ($dev !== 0) {
    $version = time();
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/html" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="keywords" content="Alphabet, Calendar, Constructor">
    <meta name="description"
          content="Educational Service: Teacher Plan Builder">
    <meta name="author" content="Lena Baydina">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Plan Builder</title>
    <link href="/favicon.ico?v=<?= $version ?>" rel="icon" sizes="any">
    <link href="/icon.svg?v=<?= $version ?>" rel="icon" type="image/svg+xml">
    <link href="/apple-touch-icon.png?v=<?= $version ?>" rel="apple-touch-icon">
    <link href="/manifest.webmanifest?v=<?= $version ?>" rel="manifest">
    <link rel="stylesheet" href="css/bootstrap.min.css?v=<?= $version ?>"/>
    <link rel="stylesheet" href="css/bootstrap-icons.min.css?v=<?= $version ?>"/>
    <link rel="stylesheet" href="css/jquery-ui.min.css?v=<?= $version ?>"/>
    <link rel="stylesheet" href="css/jquery-ui.base.theme.min.css?v=<?= $version ?>"/>
    <link rel="stylesheet" href="css/tippy.min.css?v=<?= $version ?>"/>
    <link rel="stylesheet" href="css/bootstrap.treeview.min.css?v=<?= $version ?>"/>
    <link rel="stylesheet" href="css/main.css?v=<?= $version ?>"/>
    <script src="js/jquery.min.js?v=<?= $version ?>"></script>
    <script src="js/jquery-ui.min.js?v=<?= $version ?>"></script>
    <script src="js/jquery-touch.min.js?v=<?= $version ?>"></script>
    <script src="js/jquery.scrollto.min.js?v=<?= $version ?>"></script>
    <script src="js/crypto.min.js?v=<?= $version ?>"></script>
    <script src="js/bootstrap.min.js?v=<?= $version ?>"></script>
    <script src="js/bootstrap.treeview.min.js?v=<?= $version ?>"></script>
    <script src="js/jspdf.min.js?v=<?= $version ?>"></script>
    <script src="js/html2canvas.min.js?v=<?= $version ?>"></script>
    <script src="js/popper.min.js?v=<?= $version ?>"></script>
    <script src="js/tippy.min.js?v=<?= $version ?>"></script>
</head>
<body>
<script>
    var Version = '<?=$version?>';
</script>
<section id="verify" style="background-image: url('img/cover-invisible-background.png');"
         class="<?= ($verifyResult ? '' : 'd-none') ?>">
    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-4 offset-lg-4 verify-block" style="background: white">
                <h2 class="pb-2 text-center"><?= TITLE ?></h2>
                <h3>Verify account</h3>
                <div class="mt-3 alert <?= ($verifyResult && isset($verifyResult['success']) ? 'alert-success' : 'alert-danger') ?>">
                    <?= ($verifyResult && isset($verifyResult['success']) ? $verifyResult['success'] : ($verifyResult ? $verifyResult['error'] : 'Unknown error')) ?>
                </div>
                <button class="to-signin form-control btn btn-primary">Sign in</button>
            </div>
        </div>
    </div>
</section>
<section id="signin" style="background-image: url('img/cover-invisible-background.png');" class="d-none">
    <div class="container">
        <div clas="row">
            <div class="col-12 col-lg-4 offset-lg-4 signin-block" style="background: white">
                <h2 class="pb-2 text-center"><?= TITLE ?></h2>
                <h3>Sign in</h3>
                <label class="form-label" for="signin-email">Email</label>
                <input id="signin-email" class="form-control" placeholder="Enter email" type="email">
                <label class="form-label mt-2" for="signin-password">Password</label>
                <input id="signin-password" class="form-control" type="password" placeholder="Enter password">

                <input type="checkbox" id="rememberMe" checked>
                <label class="form-label mt-2" for="rememberMe">Remember me</label>

                <button id="btn-signin" class="form-control mt-3 btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none"></span>
                    <span>Sign in</span>
                </button>
                <div id="signin-result" class="mt-3 alert d-none"></div>
                <div id="to-forgot" class="link mt-2 text-primary text-decoration-underline">Forgot your password?</div>
                <button id="forgot-btn" class="mt-2 form-control btn btn-outline-primary d-none">
                    <span class="spinner-border spinner-border-sm d-none"></span>
                    <span>Recovery</span>
                </button>
                <div class="mt-3">Don't have an account?</div>
                <button class="to-signup form-control mt-2 btn btn-outline-primary">Create account</button>
            </div>
        </div>
    </div>
</section>
<section id="signup" style="background-image: url('img/cover-invisible-background.png');" class="d-none">
    <div class="container">
        <div clas="row">
            <div class="col-12 col-lg-4 offset-lg-4 signup-block" style="background: white">
                <h2 class="pb-2 text-center"><?= TITLE ?></h2>
                <h3>Sign up</h3>
                <label class="form-label" for="signup-email">Email</label>
                <input id="signup-email" class="form-control" placeholder="Enter email">
                <label class="form-label mt-2" for="signup-password">Password</label>
                <input id="signup-password" class="form-control" type="password" placeholder="Enter password">
                <label class="form-label mt-2" for="signup-name">Name</label>
                <input id="signup-name" class="form-control" placeholder="Enter name">
                <button id="signup-btn" class="form-control mt-3 btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none"></span>
                    <span>Sign up</span>
                </button>
                <div id="signup-result" class="mt-3 alert d-none"></div>
                <div class="mt-3">Have account?</div>
                <button class="to-signin form-control mt-2 btn btn-outline-primary">Sign in</button>
            </div>
        </div>
    </div>
</section>
<section id="panel" class="d-none">
    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-3 py-3">
                <h5 class="text-center pb-2"><?= TITLE ?></h5>
                <div class="user-info signin-block">
                    <div class="user-avatar">
                        <span class="spinner-border spinner-border d-none"></span>
                        <img
                                id="user-avatar"
                                alt="user photo"
                                src=""
                                loading="lazy"
                        >
                    </div>
                    <!--PAID START-->
                    <div class="menu-item" id="menu-item-draft-btn">
                        <label for="draft-btn">
                            Click here to create monthly curriculum
                        </label>
                        <button id="draft-btn"
                                class="menu-btn form-control btn btn-primary mt-2">
                            New Calendar Draft
                        </button>
                    </div>
                    <div class="menu-item" id="menu-item-name-constructor-btn">
                        <label for="name-constructor-btn">
                            Click here to create a new writing sheet
                        </label>
                        <button id="name-constructor-btn"
                                class="menu-btn form-control btn btn-primary mt-2">
                            Word Constructor
                        </button>
                    </div>
                    <!--PAID FINISH-->
                    <!--COMMON START-->
                    <div class="menu-item">
                        <button id="settings-btn" class="menu-btn form-control btn btn-primary mt-2">
                            Profile Settings
                        </button>
                    </div>
                    <div class="menu-item">
                        <button id="subscription-btn" class="menu-btn form-control btn btn-primary mt-2">
                            My Subscription
                        </button>
                    </div>
                    <div class="menu-item">
                        <button id="menu-signout-btn" class="menu-btn form-control btn btn-danger mt-2">
                            <span class="spinner-border spinner-border-sm d-none"></span>
                            <span>Sign out</span>
                        </button>
                    </div>
                    <!--COMMON FINISH-->
                    <!--ADMIN START-->
                    <div class="menu-item" id="menu-item-filemanager-btn">
                        <label for="filemanager-btn">Upload a file</label>
                        <button id="filemanager-btn" class="menu-btn form-control btn btn-success mt-2">File
                            Manager
                        </button>
                    </div>
                    <!--ADMIN FINISH-->
                </div>
            </div>
            <div class="col-12 col-lg-9">
                <!--PAID START-->
                <section id="draft" class="page d-none">
                    <div class="d-flex justify-content-between align-items-center pb-1">
                        <h4>Calendars</h4>
                        <button id="draft-add" class="btn btn-primary">Add New Draft</button>
                    </div>
                    <div class="table-responsive mt-2">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>№</th>
                                <th>Name</th>
                                <th>Month</th>
                                <th>Control</th>
                            </tr>
                            </thead>
                            <tbody id="draft-list"></tbody>
                        </table>
                    </div>
                </section>
                <section id="calendar-constructor" class="page d-none no-select">
                    <div class="d-flex flex-column h-100">
                        <div class="d-flex justify-content-between align-items-center flex-grow-0 pb-1">
                            <h4>Monthly Learning Planner</h4>
                            <div class="d-flex flex-row gap-1">
                                <button id="calendar-contructor-add" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#calendar-plus">+
                                </button>
                                <button id="calendar-constructor-save" class="btn btn-success">
                                    <span class="spinner-border spinner-border-sm d-none"></span>
                                    <span>Save</span>
                                </button>
                                <button id="calendar-constructor-generate" class="btn btn-primary ml-2">
                                    <span class="spinner-border spinner-border-sm d-none"></span>
                                    <span>Print</span>
                                </button>
                            </div>
                        </div>
                        <div class="calendar-constructor-content flex-grow-1">
                            <div id="calendar-constructor-list">
                                <div id="calendar-constructor-list-content">
                                    <div class="a4"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section id="name-constructor" class="page d-none no-select">
                    <div class="d-flex flex-column h-100">
                        <div class="d-flex justify-content-between align-items-center flex-grow-0 flex-wrap pb-1">
                            <h4 class="text-nowrap">Name constructor</h4>
                            <div class="d-flex flex-row gap-1">
                                <input id="name-constructor-name" class="form-control" placeholder="Enter name">
                                <button id="name-constructor-generate" class="btn btn-primary" disabled>
                                    <span class="spinner-border spinner-border-sm d-none"></span>
                                    <span>Generate</span>
                                </button>
                                <button id="name-constructor-print" class="btn btn-primary" disabled>
                                    <span class="spinner-border spinner-border-sm d-none"></span>
                                    <span>Print</span>
                                </button>
                            </div>
                        </div>
                        <div class="name-constructor-content flex-grow-1">
                            <div id="name-constructor-list">
                                <div id="name-constructor-list-content">
                                    <div class="a4"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!--PAID FINISH-->
                <!--COMMON START-->
                <section id="settings" class="page d-none">
                    <h1 class="pb-2">Profile Settings</h1>
                    <h4>General</h4>
                    <div class="row">
                        <div class="col-12 col-lg-6 mb-1">
                            <label class="form-label" for="settings-name">Name</label>
                            <input id="settings-name" class="form-control" placeholder="Enter name">
                        </div>
                        <div class="col-12 col-lg-6 mb-1">
                            <label class="form-label" for="settings-surname">Last Name</label>
                            <input id="settings-surname" class="form-control" placeholder="Enter last name">
                        </div>
                        <div class="col-12 col-lg-6 mb-1">
                            <label class="form-label" for="settings-gender">Gender</label>
                            <select id="settings-gender" class="form-control">
                                <option value="0" selected>No select</option>
                                <option value="1">Male</option>
                                <option value="2">Female</option>
                                <option value="3">Other</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-6 mb-1">
                            <label class="form-label" for="select-week-type">First day of the week</label>
                            <select id="select-week-type" class="form-control">
                                <option value="0" selected>Sunday</option>
                                <option value="1">Monday</option>
                            </select>
                        </div>
                        <div class="col-12 mb-1">
                            <label class="form-label" for="settings-interests">Most interested in teaching</label>
                            <input id="settings-interests" class="form-control"
                                   placeholder="Enter most interested in teaching">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="settings-about">About you (100 words)</label>
                            <input id="settings-about" class="form-control" placeholder="Enter about you">
                        </div>
                        <div class="col-12">
                            <div id="save-general-result" class="alert d-none mt-3"></div>
                        </div>
                        <div class="col-12 col-lg-4 offset-lg-4 mt-2 mb-5">
                            <button id="save-general-btn" class="form-control btn btn-primary">
                                <span class="spinner-border spinner-border-sm d-none"></span>
                                <span>Save</span>
                            </button>
                        </div>
                        <div class="clearfix"></div>
                        <div class="col-12 col-lg-4">
                            <h4>Profile Picture</h4>
                            <div class="user-avatar">
                                <span class="spinner-border spinner-border d-none"></span>
                                <img
                                        id="settings-user-avatar"
                                        style="object-fit: contain"
                                        alt="user photo preview"
                                        src=""
                                        loading="lazy"
                                >
                            </div>
                            <div id="settings-avatar-result" class="alert d-none"></div>
                            <button id="settings-avatar-remove-btn" class="form-control btn btn-danger d-none">
                                <span class="spinner-border spinner-border-sm d-none"></span>
                                <span>Remove</span>
                            </button>
                            <button id="settings-avatar-upload-btn" class="form-control btn btn-primary my-2">
                                <span class="spinner-border spinner-border-sm d-none"></span>
                                <span>Upload</span>
                            </button>
                        </div>
                        <div class="col-12 col-lg-4 mt-5 mt-lg-0">
                            <h4>Change password</h4>
                            <label class="form-label" for="settings-password">Password</label>
                            <input id="settings-password" class="form-control" placeholder="Enter new password"
                                   type="password">
                            <label class="form-label mt-2" for="settings-rpassword">Repeat password</label>
                            <input id="settings-rpassword" class="form-control" placeholder="Enter password again"
                                   type="password">
                            <div id="settings-password-result" class="alert d-none mt-3"></div>
                            <button id="settings-password-btn" class="form-control btn btn-primary mt-2">
                                <span class="spinner-border spinner-border-sm d-none"></span>
                                <span>Change password</span>
                            </button>
                        </div>
                        <div class="col-12 col-lg-4 mt-5 mt-lg-0">
                            <h4>Change email</h4>
                            <label class="form-label" for="settings-email">Email</label>
                            <input id="settings-email" class="form-control" type="email">
                            <label class="form-label d-none mt-2" for="settings-email-code">Confirm code</label>
                            <input id="settings-email-code" class="form-control d-none">
                            <div id="settings-email-result" class="alert d-none mt-3"></div>
                            <button id="settings-email-btn" class="form-control btn btn-primary mt-2">
                                <span class="spinner-border spinner-border-sm d-none"></span>
                                <span>Change email</span>
                            </button>
                        </div>
                        <div class="hr my-5"></div>
                        <div class="text-center">Do you want to end your session?</div>
                        <div class="text-center mt-2 mb-2">
                            <button id="signout-btn" class="btn btn-danger">Sign out</button>
                        </div>
                    </div>
                </section>
                <section id="subscription" class="page d-none">
                    <h1 class="pb-2">Shop</h1>
                    <h4>Items</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="shop-item">
                                <p class="title">Young Reader Textbook (Prek-1) Elena Baydina</p>
                                <div class="shop-img">
                                    <img
                                            src="img/shop/book/cover1.jpg"
                                            alt="book cover"
                                            loading="lazy"
                                    >
                                </div>
                                <p>
                                    Rooted in structured literacy principles and phonics-based instruction, this book
                                    offers:
                                </p>
                                <ul>
                                    <li>
                                        ✔ Step-by-step lessons to support foundational reading and writing development.
                                    </li>
                                    <li>
                                        ✔ Beautifully illustrated resources to engage young learners.
                                    </li>
                                    <li>
                                        ✔ Hands-on, adaptable activities for diverse learning needs, including ELL
                                        students.
                                    </li>
                                    <li>
                                        ✔ Proven strategies to close literacy gaps and build confident, capable readers.
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="shop-item">
                                <p class="title">Teacher Plan Builder App</p>
                                <div class="shop-img">
                                    <img
                                            src="img/shop/app/TPB%20-%20app%20example.png"
                                            alt="calendar"
                                            loading="lazy"
                                    >
                                </div>
                                <ul>
                                    <li>
                                        ✔ Comprehensive Lesson & Unit Planning – Organize instruction through a
                                        concept-based framework adaptable for various grade levels.
                                    </li>
                                    <li>
                                        ✔ Extensive Literacy Resource Library – Access phonics lessons, reading
                                        strategies, and structured literacy activities to support diverse learners,
                                        including ELL students.
                                    </li>
                                    <li>
                                        ✔ Comes with the writing Name Constructor – Create personalized writing
                                        exercises to reinforce early literacy skills.
                                    </li>
                                    <li>
                                        ✔ Concept-Based Learning Tools – Build interdisciplinary units that connect
                                        literacy with broader themes and inquiry-based learning.
                                    </li>
                                    <li>
                                        ✔ Daily Instruction Calendar – Generate a month-long instructional roadmap to
                                        guide daily lessons and student learning, ensuring consistency and progress.
                                    </li>
                                    <li>
                                        ✔ Adaptable for Homeschooling & Traditional Classrooms – Customizable resources
                                        for different instructional needs.
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="shop-item">
                                <p class="title">Name Constructor</p>
                                <div class="shop-img">
                                    <img
                                            src="img/shop/name-constructor/name-example.webp"
                                            alt="name-example"
                                            loading="lazy"
                                    >
                                </div>
                                <p>
                                    The Name Constructor is an interactive tool designed to help students practice
                                    writing
                                    words they are learning, including sight words, their own names, and
                                    uppercase/lowercase
                                    letters.
                                </p>
                                <ul>
                                    <li>
                                        ✔ Personalized Name Practice: Generates worksheets for students to practice
                                        writing their own names in both uppercase and lowercase letters.
                                    </li>
                                    <li>
                                        ✔ Sight Word Integration: Allows teachers to input custom word lists for
                                        structured practice.
                                    </li>
                                    <li>
                                        ✔ Adaptive Formatting: Provides dashed-line tracing, guided writing, and
                                        freehand spaces to support different skill levels.
                                    </li>
                                    <li>
                                        ✔ Printable & Digital Use: Worksheets can be printed for handwriting practice or
                                        used on tablets with a stylus.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <table id='item-list' class="table table-hover table-bordered border-primary">
                            <caption>List of products</caption>
                            <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Note</th>
                                <th scope="col">Price</th>
                                <th scope="col">Buy</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <th scope="row">Teacher Plan Builder - individual</th>
                                <td>Per year</td>
                                <td>$150.00 USD</td>
                                <td>
                                    <form action="/checkout.php?priceId=<?= ANNUAL_SUBSCRIPTION_INDIVIDUAL ?>"
                                          method="POST">
                                        <button type="submit" id="renew-monthly-btn"
                                                class="form-control btn btn-primary my-2">
                                            <span>Buy</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Teacher Plan Builder - individual</th>
                                <td>Per month</td>
                                <td>$15.00 USD</td>
                                <td>
                                    <form action="/checkout.php?priceId=<?= MONTHLY_SUBSCRIPTION_INDIVIDUAL ?>"
                                          method="POST">
                                        <button type="submit" id="renew-monthly-btn"
                                                class="form-control btn btn-primary my-2">
                                            <span>Buy</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Teacher Plan Builder - for schools</th>
                                <td>Per year</td>
                                <td>$450.00 USD</td>
                                <td>
                                    <form action="/checkout.php?priceId=<?= ANNUAL_SUBSCRIPTION_SCHOOL ?>"
                                          method="POST">
                                        <button type="submit" id="renew-monthly-btn"
                                                class="form-control btn btn-primary my-2">
                                            <span>Buy</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Teacher Plan Builder - for schools</th>
                                <td>Per month</td>
                                <td>$39.99 USD</td>
                                <td>
                                    <form action="/checkout.php?priceId=<?= MONTHLY_SUBSCRIPTION_SCHOOL ?>"
                                          method="POST">
                                        <button type="submit" id="renew-monthly-btn"
                                                class="form-control btn btn-primary my-2">
                                            <span>Buy</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="book-with-discount">
                                <th scope="row">Young Reader Workbook with subscription</th>
                                <td>book</td>
                                <td>$52.00 USD</td>
                                <td>
                                    <form action="/checkout.php?priceId=<?= BOOK_WITH_SUBSCRIPTION ?>&book=true"
                                          method="POST">
                                        <button type="submit" id="book-with-subscription"
                                                class="form-control btn btn-primary my-2">
                                            <span>Buy</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="book-full-price">
                                <th scope="row">Young Reader Workbook without subscription</th>
                                <td>book</td>
                                <td>$62.00 USD</td>
                                <td>
                                    <form action="/checkout.php?priceId=<?= FULL_PRICE_OF_BOOK ?>&book=true"
                                          method="POST">
                                        <button type="submit" id="full-price-of-book"
                                                class="form-control btn btn-primary my-2">
                                            <span>Buy</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <h4>My Subscriptions</h4>
                    <div class="row">
                        <table
                                id='subscription-list'
                                class="table table-hover
                                    table-bordered border-primary">
                            <caption>
                                List of my actual subscriptions
                            </caption>
                            <thead>
                            <tr>
                                <th scope="col">Title</th>
                                <th scope="col">Type</th>
                                <th scope="col">Starts</th>
                                <th scope="col">Ends</th>
                                <th scope="col">Cancel at period end</th>
                                <th scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </section>
                <!--COMMON FINISH-->
                <section id="page-loader" class="d-none py-3">
                    <span class="loader-spinner spinner-border"></span>
                    <span class="loader-text text-center d-none"></span>
                </section>
            </div>
        </div>
    </div>
</section>
<section id="loader" class="d-none">
    <span class="spinner-border"></span>
</section>
<section id="global-result" class="d-none">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="alert"></div>
            </div>
        </div>
    </div>
</section>
<input id="image-uploader" class="d-none" type="file" accept=".png, .jpg, .jpeg">
<img
        id="copyright"
        src="img/copyright.png?v=<?= $version ?>"
        class="d-none"
        alt="copyright"
        loading="lazy"
>
<div class="modal fade" id="calendar-month-selector" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="calendar-months">Month</label>
                <select id="calendar-months" class="form-control"></select>
                <label class="form-label mt-2" for="calendar-years">Year</label>
                <select id="calendar-years" class="form-control"></select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="calendar-constructor-add-sheet" type="button" data-bs-dismiss="modal"
                        class="btn btn-primary">Add sheet
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="calendar-plus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Select what you want to add to the sheet</label>
                <div class="d-flex justify-content-between">
                    <button id="calendar-to-add-image" data-bs-dismiss="modal" class="btn btn-primary"><i
                                class="bi bi-image-fill"></i> Image
                    </button>
                    <button id="calendar-to-edit-text" data-bs-dismiss="modal" class="btn btn-primary"><i
                                class="bi bi-fonts"></i> Concepts
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="calendar-to-add-image-type" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Select what you want to add to the sheet</label>
                <div class="d-flex justify-content-between">
                    <button id="calendar-to-add-image-type-all" data-bs-dismiss="modal" class="btn btn-primary"><i
                                class="bi bi-images"></i> All month images
                    </button>
                    <button id="calendar-to-add-image-type-one" data-bs-dismiss="modal" class="btn btn-primary"><i
                                class="bi bi-image-fill"></i> One image
                    </button>
                </div>
                <div class="row">
                    <div class="col col-md-6 mt-3">
                        <label class="form-label">Image Categories
                            <i id="calendar-image-editor-helper"
                               class="bi bi-question-circle-fill">
                            </i>
                        </label>
                        <div id="calendar-constructor-edit-image-search"></div>
                    </div>
                    <div class="col">
                        <div id="calendar-image-editor-result" class="alert alert-danger mt-2 d-none"></div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="calendar-image-editor-add" type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    Add
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="calendar-text-editor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Concept formatting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col col-md-6">
                        <label class="form-label">Concept <i id="calendar-editor-helper"
                                                             class="bi bi-question-circle-fill"></i></label>
                        <div id="calendar-constructor-edit-text-search"></div>
                    </div>
                    <div class="col col-md-6">
                        <div class="row">
                            <div class="col">
                                <label class="form-label mt-2" for="calendar-constructor-edit-size">Size</label>
                                <input id="calendar-constructor-edit-size" class="form-control" type="number" step="1"
                                       min="8" value="20" max="100">
                            </div>
                            <div class="col">
                                <label class="form-label mt-2" for="calendar-constructor-edit-color">Color</label>
                                <input id="calendar-constructor-edit-color" class="form-control form-control-color"
                                       type="color" value="#000000">
                            </div>
                            <div class="row">
                                <label class="form-label mt-3" for="calendar-constructor-edit-preview">Preview</label>
                                <div id="calendar-constructor-edit-preview">Concept</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div id="calendar-text-editor-result" class="alert alert-danger mt-2 d-none"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="calendar-text-editor-add" type="button" class="btn btn-primary">Add</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="calendar-day-formatting" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Day formatting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col">
                        <label class="form-label mt-2" for="calendar-day-color">Select color</label>
                        <input id="calendar-day-color" class="form-control form-control-color" type="color"
                               value="#000000">
                    </div>
                    <div class="col">
                        <label class="form-label mt-2" for="calendar-day-holiday-color">Holiday</label>
                        <button id="calendar-day-holiday-color" data-bs-dismiss="modal"
                                class="form-control btn btn-primary">Holiday
                        </button>
                    </div>
                    <div class="col">
                        <label class="form-label mt-2" for="calendar-day-clear-color">Clear</label>
                        <button id="calendar-day-clear-color" data-bs-dismiss="modal"
                                class="form-control btn btn-primary">Clear
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="calendar-day-color-save" type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="calendar-images-window" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h4>Month images <i class="calendar-images-window-help bi bi-question-circle-fill"></i></h4>
                <div id="month-images"></div>
                <h4>Images by letter <i class="calendar-images-window-help bi bi-question-circle-fill"></i></h4>
                <input id="letter-images-search" class="form-control" placeholder="Search">
                <div id="letter-images" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="calendar-constructor-save-window" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Save sheet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label mt-2" for="calendar-save-sheet-name">Name</label>
                <input id="calendar-save-sheet-name" class="form-control" placeholder="Enter name">
                <div id="calendar-constructor-saved-result" class="alert mt-2 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="calendar-constructor-saved" type="button" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none"></span>
                    <span>Save</span>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="calendar-load-autosave" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unsaved sheet data found</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Autosave tool saved your latest changes to a worksheet, load an unsaved worksheet?
            </div>
            <div class="modal-footer">
                <button id="calendar-autosaved-load" type="button" data-bs-dismiss="modal" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none"></span>
                    <span>Load last sheet</span>
                </button>
                <button id="calendar-autosaved-new" type="button" data-bs-dismiss="modal" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none"></span>
                    <span>Add New Draft</span>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="dialog-confirm-add-text" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm add concepts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                You confirm the addition of the selected <span id="dialog-confirm-add-text-selected">0</span> concepts?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="calendar-text-editor-add-confirmed" type="button" class="btn btn-primary"
                        data-bs-dismiss="modal">Ok
                </button>
            </div>
        </div>
    </div>
</div>
<div id="calendar-constructor-tmp"></div>
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <p class="text-md-center">Elena&nbsp;Baydina©2025
                    <a href="mailto:teacherplanbuilder@gmail.com">
                        teacherplanbuilder@gmail.com
                    </a>
                </p>
            </div>
        </div>
    </div>
</footer>
<script>
    var Host = '<?=HOST?>';
    var HostClear = Host.lastIndexOf('/') === Host.length - 1 ? Host.substring(0, Host.length - 1) : Host;
    var clientToken = '<?=$_COOKIE['csrf_token']?>';
    var userToken = '';
    var alphabet = 'abcdefghijklmnopqrstuvwxyz'.split('');
    var user, nameConstructorImages, calendarConstructorImages,
        sheets = {}, CalendarElements = [], selectedElement = false,
        selectDay, searchText = '';
    var currentCalendarConstructorContainer = $("#calendar-constructor-list-content .a4");
    var sheet = false;
    var autoSaveData = null;
    var calendarImages;
    var conceptsList;
    var selectedConcept = false;
    var guestMenuWasInitialized = false;
    var constructorWasInitialized = false;
</script>
<script src="js/main.js?v=<?= $version ?>"></script>
</body>
</html>
