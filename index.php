<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'Backend/autoload.php';
use Calendar\Functions;
session_start();
$api = new Calendar\Api($db, false);
define('V', time());
if(!isset($_COOKIE['csrf_token'])){
    $_COOKIE['csrf_token'] = Functions::csrfToken();
    setcookie('csrf_token', $_COOKIE['csrf_token']);
}
if(isset($_GET['verify'])){
    $_SESSION['verify'] = $_GET['verify'];
    return Functions::redirect('/', true);
}
$verifyResult = false;
if(isset($_SESSION['verify'])){
    $api->param('code', $_SESSION['verify']);
    $verifyResult = $api->apiVerify();
    if(!is_array($verifyResult)){
        $verifyResult = [
            'success' => $verifyResult
        ];
    }
    unset($_SESSION['verify']);
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css?v=<?=VERSION?>" />
    <link rel="stylesheet" href="css/bootstrap-icons.min.css?v=<?=VERSION?>" />
    <link rel="stylesheet" href="css/jquery-ui.min.css?v=<?=VERSION?>" />
    <link rel="stylesheet" href="css/jquery-ui.base.theme.min.css?v=<?=VERSION?>" />
    <link rel="stylesheet" href="css/tippy.min.css?v=<?=VERSION?>" />
    <link rel="stylesheet" href="css/bootstrap.treeview.min.css?v=<?=VERSION?>" />
    <link rel="stylesheet" href="css/main.css?v=<?=time()?>" />
    <script src="js/jquery.min.js?v=<?=VERSION?>"></script>
    <script src="js/jquery-ui.min.js?v=<?=VERSION?>"></script>
    <script src="js/jquery-touch.min.js?v=<?=VERSION?>"></script>
    <script src="js/jquery.scrollto.min.js?v=<?=VERSION?>"></script>
    <script src="js/crypto.min.js?v=<?=VERSION?>"></script>
    <script src="js/bootstrap.min.js?v=<?=VERSION?>"></script>
    <script src="js/bootstrap.treeview.min.js?v=<?=VERSION?>"></script>
    <script src="js/jspdf.min.js?v=<?=VERSION?>"></script>
    <script src="js/html2canvas.min.js?v=<?=VERSION?>"></script>
    <script src="js/popper.min.js?v=<?=VERSION?>"></script>
    <script src="js/tippy.min.js?v=<?=VERSION?>"></script>
</head>
<body>
    <section id="verify" class="<?=($verifyResult ? '' : 'd-none')?>">
        <div class="container">
            <div clas="row">
                <h1 class="py-5 text-center"><?=TITLE?></h1>
                <div class="col-12 col-lg-4 offset-lg-4 verify-block">
                    <h3>Verify account</h3>
                    <div class="mt-3 alert <?=($verifyResult && isset($verifyResult['success']) ? 'alert-success' : 'alert-danger')?>">
                        <?=($verifyResult && isset($verifyResult['success']) ? $verifyResult['success'] : ($verifyResult ? $verifyResult['error'] : 'Unknown error'))?>
                    </div>
                    <button class="to-signin form-control btn btn-primary">Sign in</button>
                </div>
            </div>
        </div>
    </section>
    <section id="signin" class="d-none">
        <div class="container">
            <div clas="row">
                <h1 class="py-5 text-center"><?=TITLE?></h1>
                <div class="col-12 col-lg-4 offset-lg-4 signin-block">
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
    <section id="signup" class="d-none">
        <div class="container">
            <div clas="row">
                <h1 class="py-5 text-center"><?=TITLE?></h1>
                <div class="col-12 col-lg-4 offset-lg-4 signup-block">
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
                    <h5 class="text-center pb-2"><?=TITLE?></h5>
                    <div class="user-info">
                        <div class="user-avatar">
                            <span class="spinner-border spinner-border d-none"></span>
                            <img id="user-avatar" class="d-none" style="object-fit: contain" alt="user photo" src="">
                        </div>
                        <button id="draft-btn" class="form-control btn btn-primary mt-2">Draft</button>
                        <button id="name-constructor-btn" class="form-control btn btn-primary mt-2">Name constructor</button>
                        <button id="settings-btn" class="form-control btn btn-primary mt-2">Profile Settings</button>
                        <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                            <button id="filemanager-btn" class="form-control btn btn-success mt-2">File Manager</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-lg-9">
                    <section id="draft" class="page d-none">
                        <div class="d-flex justify-content-between align-items-center pb-1">
                            <h4>Sheets</h4>
                            <button id="draft-add" class="btn btn-primary">Add sheet</button>
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
                                    <button id="calendar-contructor-add" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#calendar-plus">+</button>
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
                            <div class="col-12 mb-1">
                                <label class="form-label" for="settings-interests">Most interested in teaching</label>
                                <input id="settings-interests" class="form-control" placeholder="Enter most interested in teaching">
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
                                    <img id="settings-user-avatar" class="d-none" style="object-fit: contain" alt="user photo preview" src="">
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
                                <input id="settings-password" class="form-control" placeholder="Enter new password" type="password">
                                <label class="form-label mt-2" for="settings-rpassword">Repeat password</label>
                                <input id="settings-rpassword" class="form-control" placeholder="Enter password again" type="password">
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
                                <h4 class="mt-3">First day of the week</h4>
                                <select id="select-week-type" class="form-control">
                                    <option value="0" selected>Sunday</option>
                                    <option value="1">Monday</option>
                                </select>
                            </div>
                            <div class="hr my-5"></div>
                            <div class="text-center">Do you want to end your session?</div>
                            <div class="text-center mt-2 mb-2">
                                <button id="signout-btn" class="btn btn-danger">Sign out</button>
                            </div>
                        </div>
                    </section>
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
    <img id="copyright" src="img/copyright.png?v=<?=VERSION?>" class="d-none">
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
                    <button id="calendar-constructor-add-sheet" type="button"  data-bs-dismiss="modal" class="btn btn-primary">Add sheet</button>
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
                        <button id="calendar-to-add-image" data-bs-dismiss="modal" class="btn btn-primary"><i class="bi bi-image-fill"></i> Image</button>
                        <button id="calendar-to-edit-text" data-bs-dismiss="modal" class="btn btn-primary"><i class="bi bi-fonts"></i> Concepts</button>
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
                        <button id="calendar-to-add-image-type-all" data-bs-dismiss="modal" class="btn btn-primary"><i class="bi bi-images"></i> All month images</button>
                        <button id="calendar-to-add-image-type-one" data-bs-dismiss="modal" class="btn btn-primary"><i class="bi bi-image-fill"></i> One image</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                            <label class="form-label">Concept <i id="calendar-editor-helper" class="bi bi-question-circle-fill"></i></label>
                            <div id="calendar-constructor-edit-text-search"></div>
                        </div>
                        <div class="col col-md-6">
                            <div class="row">
                                <div class="col">
                                    <label class="form-label mt-2" for="calendar-constructor-edit-size">Size</label>
                                    <input id="calendar-constructor-edit-size" class="form-control" type="number" step="1" min="8" value="20" max="100">
                                </div>
                                <div class="col">
                                    <label class="form-label mt-2" for="calendar-constructor-edit-color">Color</label>
                                    <input id="calendar-constructor-edit-color" class="form-control form-control-color" type="color" value="#000000">
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
                            <input id="calendar-day-color" class="form-control form-control-color" type="color" value="#000000">
                        </div>
                        <div class="col">
                            <label class="form-label mt-2" for="calendar-day-holiday-color">Holiday</label>
                            <button id="calendar-day-holiday-color" data-bs-dismiss="modal" class="form-control btn btn-primary">Holiday</button>
                        </div>
                        <div class="col">
                            <label class="form-label mt-2" for="calendar-day-clear-color">Clear</label>
                            <button id="calendar-day-clear-color" data-bs-dismiss="modal" class="form-control btn btn-primary">Clear</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button id="calendar-day-color-save" type="button" class="btn btn-primary" data-bs-dismiss="modal">Save</button>
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
                        <span>Add new sheet</span>
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
                    <button id="calendar-text-editor-add-confirmed" type="button" class="btn btn-primary" data-bs-dismiss="modal">Ok</button>
                </div>
            </div>
        </div>
    </div>
    <div id="calendar-constructor-tmp"></div>
    <script>
        var Host = '<?=HOST?>';
        var HostClear = Host.lastIndexOf('/') === Host.length - 1 ? Host.substring(0, Host.length - 1) : Host;
        var Version = '<?=VERSION?>';
        var clientToken = '<?=$_COOKIE['csrf_token']?>';
        var userToken = '';
        var alphabet = 'abcdefghijklmnopqrstuvwxyz'.split('');
        var user, nameConstructorImages, calendarConstructorImages, sheets = {}, CalendarElements = [], selectedElement, selectDay, searchText = '';
        var currentCalendarConstructorContainer = $("#calendar-constructor-list-content .a4");
        var sheet = false;
        var autoSaveData = null;
        var calendarTexts = <?=json_encode($api->apiCalendarConstructorTexts(), JSON_UNESCAPED_UNICODE)?>;
        var calendarImages = <?=json_encode($api->apiCalendarConstructorImages(), JSON_UNESCAPED_UNICODE)?>;
        var conceptsList = <?=json_encode($api->apiGetConcepts(), JSON_UNESCAPED_UNICODE)?>;
        var selectedConcept = false;
    </script>
    <script src="js/main.js?v=<?=time()?>"></script>
</body>
</html>
