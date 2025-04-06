<?php

namespace Calendar;

use mysqli_result;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Subscription;
use Throwable;

class Api
{
    private Db $db;
    private string $logPath = '';
    private string $stripeCustomerTable = '';
    private string $stripeSecretKey = '';

    /**
     * @throws Throwable
     */
    public function __construct(Db $db, $letHandle = true)
    {
        ini_set('memory_limit', -1);

        if (defined('LOG_PATH')) {
            $parts = [
                constant('LOG_PATH'),
                time()
                . '-'
                . pathinfo(__FILE__, PATHINFO_FILENAME)
                . '.log',
            ];
            $this->logPath = join(DIRECTORY_SEPARATOR, $parts);
        }
        if (defined('STRIPE_CUSTOMER')) {
            $this->stripeCustomerTable = constant('STRIPE_CUSTOMER');
        }
        if (defined('STRIPE_SECRET_KEY')) {
            $this->stripeSecretKey = constant('STRIPE_SECRET_KEY');
        }
        $this->db = $db;

        if ($letHandle) {
            try {
                $this->db->transaction(Db::BEGIN);
                $this->handle();
                $this->db->transaction(Db::COMMIT);
            } catch (Throwable $e) {
                $this->db->transaction(Db::ROLLBACK);
                $this->logException(
                    $e,
                    'Failure handle API call',
                    get_defined_vars()
                );

                throw $e;
            }
        }
    }

    public function handle(): void
    {
        $method = 'api' . (isset($_POST['method']) ? $_POST['method'] : '');
        $response = $this->error('Method not found');
        if (isset($_POST['method']) && method_exists($this, $method)) {
            $response = call_user_func([$this, $method]);
            if (!is_array($response) || !isset($response['error'])) {
                $response = [
                    'success' => $response
                ];
            }
        }
        header('Content-Type: application/json; charset=utf-8;');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    private function error($text)
    {
        return [
            'error' => $text
        ];
    }

    /**
     * @param Throwable $e
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logException(
        Throwable $e,
        string $message = '',
        array $context = [],
    ): void {
        $exceptionDetails = [
            'Throwable' => var_export($e, true),
        ];
        $exceptionDetails += $context;

        $this->log($message, $exceptionDetails);
    }

    /**
     * @param string $message
     * @param array $details
     * @return void
     */
    public function log(
        string $message,
        array $details,
    ): void {
        $logPath = $this->logPath;
        $isPossible = realpath($logPath) !== false;

        $testMode = 'unknown';
        if ($isPossible && defined('TEST_MODE')) {
            $testMode = constant('TEST_MODE');
        }

        if ($isPossible) {
            $details['TEST_MODE'] = $testMode;

            file_put_contents(
                $logPath,
                date(DATE_ATOM, time())
                . ': '
                . $message
                . ', context: '
                . json_encode(
                    $details,
                    JSON_NUMERIC_CHECK
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                ),
                FILE_APPEND,
            );
        }
    }

    public function apiGetConcepts(): array
    {
        if (!($_SESSION['isAllow'] ?? false)) {
            return [];
        }

        $fileName = $_SERVER['DOCUMENT_ROOT'] . '/content/Concepts.xlsx';
        $fileJson = sys_get_temp_dir() . '/Concepts.json';
        $list = [];
        if (!file_exists($fileName)) {
            return $list;
        }
        $dataJson = file_exists($fileJson)
            ? json_decode(file_get_contents($fileJson), true)
            : [
                'last_modify' => 0,
                'data' => []
            ];
        $lastModify = filemtime($fileName);
        if (
            $lastModify !== false
            && $dataJson['last_modify'] === $lastModify
        ) {
            return $dataJson['data'];
        }
        $reader = new Xlsx();
        $spreadsheet = $reader->load($fileName);
        $data = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $columns = false;
            $rowsData = [];
            foreach ($sheet->getRowIterator() as $i => $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $k => $cell) {
                    $value = $cell->getValue();
                    $type = 0;
                    $name = '';
                    if (is_object($value)) {
                        foreach ($value->getRichTextElements() as $richTextElement) {
                            if ($richTextElement->getFont()->getBold()) {
                                $type = 1;
                                $name = $richTextElement->getText();
                                break;
                            }
                        }
                        $value = $value->getPlainText();
                    } else {
                        $type = $cell->getStyle()->getFont()->getBold();
                        $name = $type ? $value : '';
                    }
                    $rowData[$k] = [
                        'type' => $type,
                        'name' => trim(strval($name)),
                        'value' => trim(strval($value))
                    ];
                }
                $rowData = array_values($rowData);
                if ($i === 1) {
                    $sumBold = 0;
                    foreach ($rowData as $k => $column) {
                        if ($column['type'] == 1 || !mb_strlen($column['value'])) {
                            $sumBold++;
                        }
                    }
                    if (count($rowData) != $sumBold || $sumBold == 0) {
                        $columns = false;
                    } else {
                        $columns = $rowData;
                        continue;
                    }
                }
                if ($columns) {
                    foreach ($columns as $k => $column) {
                        $column = trim($column['value']);
                        if (mb_strlen($column)) {
                            if (!isset($rowsData[$column])) {
                                $rowsData[$column] = [];
                            }
                            $rowsData[$column][] = $rowData[$k];
                        }
                    }
                } else {
                    foreach ($rowData as $cell) {
                        $rowsData[] = $cell;
                    }
                }
            }
            if ($columns) {
                foreach ($rowsData as $k => $rowData) {
                    $rowsData[$k] = $this->correctConceptList($rowData);
                }
            } else {
                $rowsData = $this->correctConceptList($rowsData);
            }

            $title = trim($sheet->getTitle());
            if (!isset($data[$title])) {
                $data[$title] = $rowsData;
            }
        }
        $data = $this->correctConcepts($data);
        $dataJson['last_modify'] = $lastModify;
        $dataJson['data'] = $data;
        file_put_contents($fileJson, json_encode($dataJson));

        return $data;
    }

    private function correctConceptList(array $rowsData)
    {
        $tmpRowsData = [];
        $tmpArray = [];
        $tmpName = '';
        $hasBold = false;
        foreach ($rowsData as $k => &$rowData) {
            if ($rowData['type']) {
                $hasBold = true;
                if ($rowData['name'] !== $tmpName) {
                    if (count($tmpArray)) {
                        if (mb_strlen($tmpName)) {
                            $tmpRowsData[] = [
                                'text' => $tmpName,
                                'nodes' => $tmpArray
                            ];
                        } else {
                            $tmpRowsData[] = $tmpArray;
                        }
                    }
                }
                $tmpArray = [];
                $tmpName = $rowData['name'];
            }
            $rowData = [
                'text' => str_replace("\n", "<br>", $rowData['value'])
            ];
            if (!mb_strlen($rowData['text'])) {
                unset($rowsData[$k]);
            } else {
                $tmpArray[] = $rowData;
            }
        }
        if (count($tmpArray)) {
            if (mb_strlen($tmpName)) {
                $tmpRowsData[] = [
                    'text' => $tmpName,
                    'nodes' => $tmpArray
                ];
            } else {
                $tmpRowsData[] = $tmpArray;
            }
        }
        return $hasBold ? $tmpRowsData : $rowsData;
    }

    public function correctConcepts(array $rows)
    {
        $list = [];
        foreach ($rows as $k => $row) {
            if (is_numeric($k)) {
                if (!is_array($row)) {
                    $row = [
                        "text" => $row
                    ];
                }
                $list[] = $row;
            } else {
                $list[] = [
                    "text" => $k,
                    "nodes" => $this->correctConcepts($row)
                ];
            }
        }
        return $list;
    }

    public function apiCalendarConstructorTexts()
    {
        if (!($_SESSION['isAllow'] ?? false)) {
            return [];
        }

        return $this->getFiles(CONTENT_PATH, [
            'txt'
        ]);
    }

    public function getFiles($path, $exts = [], $getContents = true)
    {
        if (is_dir($path)) {
            $list = [];
            foreach (scandir($path) as $file) {
                if ($file != '.' && $file != '..') {
                    $fileExt = false;
                    $filePath = $path . $file;
                    $fileInfo = pathinfo($filePath);
                    $fileExt = isset($fileInfo['extension']) ? mb_strtolower($fileInfo['extension']) : '';
                    if (is_file($filePath)) {
                        $extFind = false;
                        foreach ($exts as $ext) {
                            if (mb_strtolower($ext) == $fileExt) {
                                $extFind = true;
                            }
                        }
                        if (!$extFind) {
                            continue;
                        }
                    }
                    $list[is_file($filePath) ? $fileInfo['filename'] : $file] = $this->getFiles(
                        $path . $file . (is_file($filePath) ? '' : '/'),
                        $exts,
                        $getContents
                    );
                }
            }
            return $list;
        }
        return $getContents ? str_replace("\n", '<br>', file_get_contents($path)) : str_replace(HOST_PATH, HOST, $path);
    }

    public function apiCalendarConstructorImages()
    {
        if (!($_SESSION['isAllow'] ?? false)) {
            return [];
        }

        return $this->getFiles(CALENDAR_IMAGES_PATH, [
            'jpg',
            'jpeg',
            'png'
        ], false);
    }

    public function apiCalendarConstructorSheetRemove()
    {
        if (!($_SESSION['isAllow'] ?? false)) {
            return $this->error('No rights');
        }

        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        $calendar = $this->query(
            "select * from calendar_constructor where id=?",
            intval($this->param('id'))
        )->fetch_assoc();
        if (!$calendar) {
            return $this->error('Calendar not found');
        }
        if ($calendar['user_id'] !== $user['id']) {
            return $this->error('No rights');
        }
        if (!$this->query("delete from calendar_constructor where id=?", $calendar['id'])) {
            return $this->error('Unknown error');
        }
        return 'Calendar removed';
    }

    public function session($isAdmin = false, $token = '')
    {
        if (!$token) {
            $token = $this->param('token', '');
        }
        $tokenExplode = explode('_', $token);
        if (count($tokenExplode) !== 2) {
            return 'Incorrect token';
        }
        list($sessionId, $sessionToken) = $tokenExplode;
        $session = $this
            ->query(
                "select token,client,expires,user_id,id from sessions where id=?",
                intval($sessionId)
            )
            ->fetch_assoc();

        if (!$session
            || $session['token'] !== $sessionToken
            || $session['client'] !== Functions::getClientToken()
        ) {
            return 'Session not found';
        }
        if (time() > $session['expires']) {
            return 'Session expired';
        }
        $user = $this->query("select * from users where id=?", $session['user_id'])->fetch_assoc();
        if (!$user) {
            return 'Session not found';
        }
        if ($isAdmin && !$user['admin']) {
            return 'No admin rights';
        }
        $user['session'] = $session;

        return $user;
    }

    public function param($name, $default = null)
    {
        if ($default !== null && !isset($_POST[$name])) {
            $_POST[$name] = $default;
        }
        return $_POST[$name] ?? '';
    }

    public function query(): mysqli_result|bool|int
    {
        return call_user_func_array([$this->db, 'query'], func_get_args());
    }

    public function apiCalendarConstructorSheetEdit()
    {
        if (!($_SESSION['isAllow'] ?? false)) {
            return $this->error('No rights');
        }

        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        $id = $this->paramId('id');
        $name = $this->param('name');
        if (!mb_strlen($name) || mb_strlen($name) > 30) {
            return $this->error('Calendar name must be from 1 to 30 characters');
        }
        $data = @json_decode($this->param('data'), true);
        if (!is_array($data)) {
            return $this->error('Incorrect data');
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $calendar = $this->query("select * from calendar_constructor where id=?", $id)->fetch_assoc();
        if (!$calendar) {
            return $this->error('Calendar not found');
        }
        if ($calendar['user_id'] !== $user['id']) {
            return $this->error('No rights');
        }
        $this->query("update calendar_constructor set data=?, name=? where id=?", $data, $name, $id);
        return true;
    }

    public function paramId($name)
    {
        $id = intval($this->param($name));
        return max($id, 0);
    }

    public function apiCalendarConstructorSheetAdd()
    {
        if (!($_SESSION['isAllow'] ?? false)) {
            return [];
        }

        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        $name = $this->param('name');
        if (!mb_strlen($name) || mb_strlen($name) > 30) {
            return $this->error('Calendar name must be from 1 to 30 characters');
        }
        $data = @json_decode($this->param('data'), true);
        if (!is_array($data)) {
            return $this->error('Incorrect data');
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (!$this->query(
            "insert into calendar_constructor (user_id, data, created, name) values (?,?,?,?)",
            $user["id"],
            $data,
            time(),
            $name
        )) {
            return $this->error('Unknown error');
        }
        $calendar = $this->query("select * from calendar_constructor where id=?", $this->db->insert_id)->fetch_assoc();
        if (!$calendar) {
            return $this->error('Unknown error');
        }
        $calendar['data'] = @json_decode($calendar['data'], true);
        $calendar['created'] = date('d.m.Y H:i:s', $calendar['created']);

        return $calendar;
    }

    public function apiGetCalendarConstructorSheets()
    {
        if (!($_SESSION['isAllow'] ?? false)) {
            return [];
        }

        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        $calendars = $this->query("select * from calendar_constructor where user_id=?", $user['id']);
        $list = [];
        while ($calendar = $calendars->fetch_assoc()) {
            $calendar['created'] = date('d.m.Y H:i:s', $calendar['created']);
            $calendar['data'] = @json_decode($calendar['data'], true);
            $list[] = $calendar;
        }
        return $list;
    }

    public function apiChangeEmailCode()
    {
        $email = $this->param('email');
        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Incorrect email');
        }
        if (mb_strlen($email) > 30) {
            return $this->error('Email must be to 30 characters');
        }
        $code = rand(100000, 999999);
        if (!$this->query(
            "update users set email_code=?, email_code_expires=? where id=?",
            $code,
            time() + CONFIRM_CODE_EXPIRES,
            $user['id']
        )) {
            return $this->error('Unknown error');
        }
        $this->sendEmailCode($user['email'], $code);
        return 'Email change confirmation code sent to old email';
    }

    private function sendEmailCode($email, $code)
    {
        Functions::mail($email, 'Account Confirmation, Teacher Plan Builder', 'Confirm code:<br>' . $code);
    }

    /**
     * @throws ApiErrorException
     */
    public function apiChangeEmail()
    {
        $email = $this->param('email');
        $code = intval($this->param('code'));
        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Incorrect email');
        }
        if (mb_strlen($email) > 30) {
            return $this->error('Email must be to 30 characters');
        }
        if (time() > $user['email_code_expires']) {
            return $this->error('Confirm code expired');
        }
        if ($user['email_code'] != $code) {
            return $this->error('Incorrect confirm code');
        }
        if ($email == $user['email']) {
            return $this->error('Email is the same as the previous email');
        }
        if (!$this->query("update users set email=? where id=?", $email, $user['id'])) {
            return $this->error('Unknown error');
        }

        $customerId = $this->readCustomerId($user['id']);

        if ($customerId !== '') {
            $stripe = new StripeClient($this->stripeSecretKey);
            $customer = $stripe->customers->update(
                $customerId,
                ['email' => $user['email'],]
            );

            $dump = $customer->toJSON();
            $this->query(
                <<<SQL
UPDATE $this->stripeCustomerTable set customer_json=? where user_id=?
SQL,
                $dump,
                $user['id'],
            );
        }

        return 'Email changed';
    }

    /**
     * @param int $userId
     * @return string
     */
    public function readCustomerId(
        int $userId
    ): string {
        $mysqliResult = $this
            ->query(
                <<<SQL
SELECT customer_id FROM $this->stripeCustomerTable WHERE user_id=?
SQL,
                $userId,
            );

        $stripeCustomerData = false;
        if ($mysqliResult !== false) {
            $stripeCustomerData = $mysqliResult->fetch_assoc();
        }
        $customerId = '';
        if (
            $stripeCustomerData !== false
            && !is_null($stripeCustomerData)
        ) {
            $customerId = $stripeCustomerData['customer_id'] ?? '';
        }

        return $customerId;
    }

    public function apiChangePassword()
    {
        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        $password = Functions::aesDecrypt($this->param('password'));
        if (mb_strlen($password) < 6 || mb_strlen($password) > 15) {
            return $this->error('Password must be from 6 to 15 characters');
        }
        if (password_verify($password, $user['password'])) {
            return $this->error('Password is the same as the previous password');
        }
        $password = password_hash($password, PASSWORD_DEFAULT);
        if (!$this->query("update users set password=? where id=?", $password, $user['id'])) {
            return $this->error('Unknown error');
        }
        return 'Password changed';
    }

    public function apiProfileRemoveAvatar()
    {
        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        $this->query("update users set photo=? where id=?", '', $user['id']);
        return 'Avatar removed';
    }

    public function apiProfileSaveAvatar()
    {
        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        $photo = $this->param('photo');
        if (!file_exists(UPLOADS_PATH . $photo)) {
            return $this->error('Image not found');
        }
        $this->query("update users set photo=? where id=?", $photo, $user['id']);
        return UPLOADS_LINK . $photo;
    }

    /**
     * @throws ApiErrorException
     */
    public function apiGetProfile()
    {
        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        $user['photo'] = $user['photo'] ? UPLOADS_LINK . $user['photo'] : '';

        $result = $this->fields($user, [
            'id',
            'admin',
            'name',
            'email',
            'created',
            'interests',
            'login',
            'about',
            'surname',
            'gender',
            'photo',
            'week_type'
        ]);

        $rows = [];
        $allow = false;
        $customerId = $this->readCustomerId($user['id']);
        if ($customerId !== '') {
            $stripe = new StripeClient($this->stripeSecretKey);
            $collection = $stripe->subscriptions->all(
                ['customer' => $customerId]
            );

            $actualSubscriptions = [];
            foreach ($collection as $subscription) {
                /** @var Subscription $subscription */
                $isActive = in_array(
                    $subscription->status,
                    [
                        Subscription::STATUS_ACTIVE,
                        Subscription::STATUS_TRIALING
                    ],
                    true
                );
                if ($isActive) {
                    $allow = true;

                    $startAt = $subscription->current_period_start;
                    $finishAt = $subscription->current_period_end;
                    /** @var \Stripe\Plan $plan */
                    $plan = $subscription->plan;
                    $product = $plan->product;
                    $interval = $plan->interval;

                    $actualSubscriptions[] = [
                        $startAt,
                        $finishAt,
                        $product,
                        $interval
                    ];
                }
            }

            $products = [];
            if (defined('STRIPE_PRODUCTS')) {
                $products = constant('STRIPE_PRODUCTS');
            }

            foreach ($actualSubscriptions as $next) {
                list($startAt, $finishAt, $product, $interval) = $next;
                $start = date('Y-m-d', $startAt);
                $finish = date('Y-m-d', $finishAt);
                $title = $products[$product] ?? '';
                $rows[] = <<<HTML
<tr>
    <th scope="row">$title</th>
    <td>$interval</td>
    <td>$start</td>
    <td>$finish</td>
</tr>
HTML;
            }
        }

        $productRowsHtml = implode('', $rows);

        $result['subscription-list'] = <<<HTML
<table 
    id='subscription-list' 
    class="table table-hover table-bordered border-primary">
    <caption>List of my actual subscriptions</caption>
    <thead>
    <tr>
        <th scope="col">Title</th>
        <th scope="col">Type</th>
        <th scope="col">Starts</th>
        <th scope="col">Ends</th>
    </tr>
    </thead>
    <tbody>
$productRowsHtml
    </tbody>
</table>
HTML;

        if ($user['admin'] === 1) {
            $allow = true;
        }
        $result['allow'] = $allow;

        $this->log(__FUNCTION__, ['$result' => $result]);

        return $result;
    }

    private function fields(array $data, array $on = [], array $off = [])
    {
        if (count($off)) {
            $on = [];
        }
        $arr = [];
        if (count($on)) {
            foreach ($on as $v) {
                $arr[$v] = isset($data[$v]) ? $data[$v] : '';
            }
        }
        if (count($off)) {
            $arr = $data;
            foreach ($off as $v) {
                if (isset($arr[$v])) {
                    unset($arr[$v]);
                }
            }
        }
        return $arr;
    }

    public function apiSetProfile()
    {
        try {
            $user = $this->session();
            if (!is_array($user)) {
                return $this->error($user);
            }
            $name = $this->param('name', '');
            $surname = $this->param('surname', '');
            $interests = $this->param('interests', '');
            $about = $this->param('about', '');
            $gender = intval($this->param('gender', 0));
            $week_type = intval($this->param('week_type', 0));
            if ($name != '' && (mb_strlen($name) < 2 || mb_strlen($name) > 30)) {
                return $this->error('Name must be from 2 to 30 characters');
            }
            if (mb_strlen($name) && preg_match('/[^A-z\d]/', $name)) {
                return $this->error('Name must be of Latin letters and Arabic numbers');
            }
            if ($surname != '' && (mb_strlen($surname) < 2 || mb_strlen($surname) > 15)) {
                return $this->error('Last Name must be from 2 to 15 characters');
            }
            if (mb_strlen($surname) && preg_match('/[^A-z\d]/', $surname)) {
                return $this->error('Last Name must be of Latin letters and Arabic numbers');
            }
            if (mb_strlen($interests) && preg_match('/[^A-z\d]/', $interests)) {
                return $this->error('Most interested in teaching must be of Latin letters and Arabic numbers');
            }
            if (mb_strlen($interests) > 100) {
                return $this->error('Most interested in teaching must be to 100 characters');
            }
            if (count(explode(" ", $about)) > 100) {
                return $this->error('About yourself should not have more than 100 words');
            }
            if (mb_strlen($about) && preg_match('/[^A-z\d]/', $about)) {
                return $this->error('About yourself must be of Latin letters and Arabic numbers');
            }
            if (mb_strlen($about) > 1000) {
                return $this->error('About yourself must be to 1000 characters');
            }
            if (!in_array($gender, [0, 1, 2])) {
                return $this->error('Incorrect gender');
            }
            $this->query(
                "update users set week_type=?, name=?,surname=?,interests=?,about=?,gender=? where id=?",
                $week_type,
                $name,
                $surname,
                $interests,
                $about,
                $gender,
                $user['id']
            );

            $stripeCustomerId = $this->readCustomerId($user['id']);

            if ($stripeCustomerId !== '') {
                $stripe = new StripeClient($this->stripeSecretKey);
                $customer = $stripe->customers->update(
                    $stripeCustomerId,
                    [
                        'name' => $user['name'] . ' ' . $user['surname'],
                    ]
                );

                $dump = $customer->toJSON();
                $this->query(
                    <<<SQL
UPDATE $this->stripeCustomerTable set customer_json=? where user_id=?
SQL,
                    $dump,
                    $user['id'],
                );
            }

            return 'General information saved';
        } catch (Throwable $e) {
            $this->logException(
                $e,
                'Failure on setting profile data',
                get_defined_vars(),
            );

            throw $e;
        }
    }

    public function apiVerify()
    {
        $code = $this->param('code');
        $user = $this
            ->query(
                "select email,name,surname,id,verify_code from users where verify_code=?",
                $code
            )
            ->fetch_assoc();
        if (!$user) {
            return $this->error("User not found");
        }
        if (time() > $user['verify_code']) {
            return $this->error("Verify code expired. To receive a new activation code, log in");
        }
        if ($user['verify_code'] !== $code) {
            return $this->error('Incorrect verify code');
        }
        if (!$this->query("update users set verify=1 where id=?", $user['id'])) {
            return $this->error('Unknown error');
        }

        return 'Account has been confirmed, you are now logged in to your account.';
    }

    public function apiRecovery()
    {
        $email = $this->param('email');
        $user = $this->query("select * from users where email=?", $email)->fetch_assoc();
        if (!$user) {
            return $this->error('User not found');
        }
        if (!$user['verify']) {
            return $this->error('Account not verified');
        }
        $newPassword = uniqid();
        if (!$this->query(
            "update users set recovery_password=? where id=?",
            password_hash($newPassword, PASSWORD_DEFAULT),
            $user['id']
        )) {
            return $this->error('Unknown error');
        }
        $this->sendPassword($user['email'], $newPassword);
        return 'New password has been generated and sent by email';
    }

    private function sendPassword($email, $password)
    {
        Functions::mail($email, 'Account Recovery, Teacher Plan Builder', 'Account new password:<br>' . $password);
    }

    public function apiSignout()
    {
        $user = $this->session();
        if (!is_array($user)) {
            return $this->error($user);
        }
        if (!$this->query("delete from sessions where id=?", $user['session']['id'])) {
            return $this->error('Session not found');
        }
        $_SESSION['admin'] = 0;
        $_SESSION['isAllow'] = false;
        $_SESSION['token'] = '';

        $this->log(__FUNCTION__, ['$_SESSION' => $_SESSION]);

        return 'Session destroyed';
    }

    /**
     * @throws ApiErrorException
     */
    public function apiSignin()
    {
        $email = $this->param('email');
        $password = Functions::aesDecrypt($this->param('password'));
        $user = $this->query("select * from users where email=?", $email)->fetch_assoc();
        $clientId = Functions::getClientToken();
        if (!$user) {
            return $this->error('User not found');
        }
        if ($user['recovery_password'] && password_verify($password, $user['recovery_password'])) {
            $this->query("update users set password=recovery_password,recovery_password='' where id=?", $user['id']);
        } elseif (!password_verify($password, $user['password'])) {
            return $this->error('Incorrect email or password');
        }
        if (!$user['verify']) {
            $verifyCode = md5(Functions::getClientToken() . uniqid('calendar_') . microtime(1));
            $verifyCodeExpires = time() + VERIFY_LINK_EXPIRES;
            if (!$this->query(
                "update users set verify_code=?,verify_code_expires=? where id=?",
                $verifyCode,
                $verifyCodeExpires,
                $user['id']
            )) {
                return $this->error('Unknown error');
            }
            $this->sendVerifyCode($user['email'], $verifyCode);
            return $this->error(
                'Account not verified, we resent a message with a link to confirm your account to your email'
            );
        }
        $token = md5($user['id'] . $clientId . uniqid('calendar_') . microtime(1));
        $this->query("delete from sessions where client=?", $clientId);
        if (!$this->query(
            "insert into sessions (user_id,client,token,expires) values (?,?,?,?)",
            $user['id'],
            $clientId,
            $token,
            time() + SESSION_EXPIRES
        )) {
            return $this->error('Unknown error');
        }
        $session = $this->query("select * from sessions where id=?", $this->db->insert_id)->fetch_assoc();
        if (!$session) {
            return $this->error('Unknown error');
        }

        $_SESSION['admin'] = $user['admin'];
        $isAllow = $this->hasPaidSubscription($user);
        $_SESSION['isAllow'] = $isAllow || $user['admin'] === 1;
        $_SESSION['token'] = $session['id'] . '_' . $session['token'];

        $this->log(__FUNCTION__, ['$_SESSION' => $_SESSION]);

        return $_SESSION['token'];
    }

    private function sendVerifyCode($email, $verifyCode)
    {
        $host = '';
        if (defined('HOST')) {
            $host = constant('HOST');
        }
        Functions::mail(
            $email
            ,
            'Welcome to Teacher Plan Builder'
            ,
            <<<HTML
Account activation link:<a href="$host?verify= $verifyCode">Click me to confirm account</a>
HTML
        );
    }

    /**
     * @throws ApiErrorException
     */
    private function hasPaidSubscription(array $user): bool
    {
        $has = false;
        $customerId = $this->readCustomerId($user['id']);
        if ($customerId !== '') {
            $stripe = new StripeClient($this->stripeSecretKey);
            $collection = $stripe->subscriptions->all(
                ['customer' => $customerId]
            );
            foreach ($collection as $subscription) {
                /** @var Subscription $subscription */
                $isActive = in_array(
                    $subscription->status,
                    [
                        Subscription::STATUS_ACTIVE,
                        Subscription::STATUS_TRIALING
                    ],
                    true
                );
                if ($isActive) {
                    $has = true;
                    break;
                }
            }
        }

        return $has;
    }

    public function apiSignup()
    {
        $email = $this->param('email');
        $password = Functions::aesDecrypt($this->param('password'));
        $name = $this->param('name');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Incorrect email');
        }
        if (mb_strlen($email) > 30) {
            return $this->error('Email must be to 30 characters');
        }
        if (mb_strlen($password) < 6 || mb_strlen($password) > 30) {
            return $this->error('Password must be from 6 to 30 characters');
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 30) {
            return $this->error('Name must be from 2 to 30 characters');
        }
        if (preg_match('/[^A-z\d]/', $name)) {
            return $this->error('Name must be of Latin letters and Arabic numbers');
        }
        $user = $this->query("select * from users where email=?", $email)->fetch_assoc();
        if ($user) {
            return $this->error('Email taken');
        }
        $password = password_hash($password, PASSWORD_DEFAULT);
        $verifyCode = md5(Functions::getClientToken() . uniqid('calendar_') . microtime(1));
        $verifyCodeExpires = time() + VERIFY_LINK_EXPIRES;
        if (!$this->query(
            "insert into users (email,password,name,created,verify_code,verify_code_expires) values (?,?,?,?,?,?)",
            $email,
            $password,
            $name,
            time(),
            $verifyCode,
            $verifyCodeExpires
        )) {
            return $this->error('Unknown error');
        }
        $this->sendVerifyCode($email, $verifyCode);
        return 'An email has been sent to your email with link to activate your account. Follow it and you will be able to successfully log in to the site. The link is valid for an hour.';
    }

    public function uploadFile()
    {
        $user = $this->session();
        $response = $this->error('Unknown error');
        if (!is_array($user)) {
            $response = $this->error($user);
        } else {
            if (isset($_FILES['photo'])) {
                $photo = $_FILES['photo'];
                $imageData = file_get_contents($photo['tmp_name']);
                $image = imagecreatefromstring($imageData);
                if (!$image) {
                    $response = $this->error('Incorrect image format');
                } else {
                    $percent = 0.25;
                    $fileName = md5($imageData) . '.png';
                    $imageWidth = imagesx($image);
                    $imageHeight = imagesy($image);
                    $newImageWidth = ceil($imageWidth * $percent);
                    $newImageHeight = ceil($imageHeight * $percent);
                    $newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
                    imagecopyresampled(
                        $newImage,
                        $image,
                        0,
                        0,
                        0,
                        0,
                        $newImageWidth,
                        $newImageHeight,
                        $imageWidth,
                        $imageHeight
                    );
                    imagedestroy($image);
                    imagepng($newImage, UPLOADS_PATH . $fileName);
                    imagedestroy($newImage);
                    $response = [
                        'success' => [
                            'name' => $fileName,
                            'photo' => UPLOADS_LINK . $fileName
                        ]
                    ];
                }
            }
        }
        header('Content-Type: application/json; charset=utf-8;');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array $user
     * @return string
     * @throws ApiErrorException
     */
    public function createStripeUser(
        array $user
    ): string {
        $stripe = new StripeClient($this->stripeSecretKey);
        $customer = $stripe->customers->create([
            'name' => $user['name'] . ' ' . $user['surname'],
            'email' => $user['email'],
        ]);

        $customerId = $customer->id;
        $dump = $customer->toJSON();
        $this->query(
            <<<SQL
INSERT INTO $this->stripeCustomerTable
    (user_id,customer_id,customer_json)
values(?,?,?)
SQL,
            $user['id'],
            $customerId,
            $dump,
        );

        return $customerId;
    }

    private function paramIds($name)
    {
        $list = [];
        foreach (explode(",", $this->param($name)) as $id) {
            $id = intval($id);
            if ($id > 0) {
                $list[$id] = true;
            }
        }
        return array_keys($list);
    }
}
