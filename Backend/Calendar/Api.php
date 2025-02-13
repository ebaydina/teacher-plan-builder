<?php
namespace Calendar;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class Api
{
    private $db;
    public function __construct(Db $db, $init = true)
    {
        ini_set('memory_limit', -1);
        $this->db = $db;
        if($init){
            $this->handle();
        }
    }
    private function correctConceptList(array $rowsData)
    {
        $tmpRowsData = [];
        $tmpArray = [];
        $tmpName = '';
        $hasBold = false;
        foreach($rowsData as $k => &$rowData){
            if($rowData['type']){
                $hasBold = true;
                if($rowData['name'] !== $tmpName){
                    if(count($tmpArray)){
                        if(mb_strlen($tmpName)){
                            $tmpRowsData[] = [
                                'text' => $tmpName,
                                'nodes' => $tmpArray
                            ];
                        }else{
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
            if(!mb_strlen($rowData['text'])){
                unset($rowsData[$k]);
            }else{
                $tmpArray[] = $rowData;
            }
        }
        if(count($tmpArray)){
            if(mb_strlen($tmpName)){
                $tmpRowsData[] = [
                    'text' => $tmpName,
                    'nodes' => $tmpArray
                ];
            }else{
                $tmpRowsData[] = $tmpArray;
            }
        }
        return $hasBold ? $tmpRowsData : $rowsData;
    }
    public function correctConcepts(array $rows)
    {
        $list = [];
        foreach($rows as $k => $row){
            if(is_numeric($k)){
                if(!is_array($row)){
                    $row = [
                        "text" => $row
                    ];
                }
                $list[] = $row;
            }else{
                $list[] = [
                    "text" => $k,
                    "nodes" => $this->correctConcepts($row)
                ];
            }
        }
        return $list;
    }
    public function apiGetConcepts()
    {
        $fileName = $_SERVER['DOCUMENT_ROOT'] . '/content/Concepts.xlsx';
        $fileJson = sys_get_temp_dir() . '/Concepts.json';
        $list = [];
        if(!file_exists($fileName)){
            return $list;
        }
        $dataJson = file_exists($fileJson) ? json_decode(file_get_contents($fileJson), true) : [ 'last_modify' => 0, 'data' => [] ];
        $lastModify = filemtime($fileName);
        if($dataJson['last_modify'] == $dataJson){
            return $dataJson['data'];
        }
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($fileName);
        $data = [];
        foreach($spreadsheet->getAllSheets() as $sheet){
            $title = trim($sheet->getTitle());
            if(!isset($data[$title])){
                $data[$title] = [];
            }
            $columns = false;
            $rowsData = [];
            foreach($sheet->getRowIterator() as $i => $row){
                $rowData = [];
                foreach($row->getCellIterator() as $k => $cell){
                    $value = $cell->getValue();
                    $type = 0;
                    $name = '';
                    if(is_object($value)){
                        foreach($value->getRichTextElements() as $richTextElement){
                            if($richTextElement->getFont()->getBold()){
                                $type = 1;
                                $name = $richTextElement->getText();
                                break;
                            }
                        }
                        $value = $value->getPlainText();
                    }else{
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
                if($i === 1){
                    $sumBold = 0;
                    foreach($rowData as $k => $column){
                        if($column['type'] == 1 || !mb_strlen($column['value'])){
                            $sumBold++;
                        }
                    }
                    if(count($rowData) != $sumBold || $sumBold == 0){
                        $columns = false;
                    }else{
                        $columns = $rowData;
                        continue;
                    }
                }
                if($columns){
                    foreach($columns as $k => $column){
                        $column = trim($column['value']);
                        if(mb_strlen($column)) {
                            if(!isset($rowsData[$column])){
                                $rowsData[$column] = [];
                            }
                            $rowsData[$column][] = $rowData[$k];
                        }
                    }
                }else{
                    foreach($rowData as $cell){
                        $rowsData[] = $cell;
                    }
                }
            }
            if($columns){
                foreach($rowsData as $k => $rowData){
                    $rowsData[$k] = $this->correctConceptList($rowData);
                }
            }else{
                $rowsData = $this->correctConceptList($rowsData);
            }
            $data[$title] = $rowsData;
        }
        $data = $this->correctConcepts($data);
        $dataJson['last_modify'] = time();
        $dataJson['data'] = $data;
        file_put_contents($fileJson, json_encode($dataJson));
        return $data;
    }
    public function getFiles($path, $exts = [], $getContents = true)
    {
        if(is_dir($path)){
            $list = [];
            foreach(scandir($path) as $file){
                if($file != '.' && $file != '..'){
                    $fileExt = false;
                    $filePath = $path . $file;
                    $fileInfo = pathinfo($filePath);
                    $fileExt = isset($fileInfo['extension']) ? mb_strtolower($fileInfo['extension']) : '';
                    if(is_file($filePath)){
                        $extFind = false;
                        foreach($exts as $ext){
                            if(mb_strtolower($ext) == $fileExt){
                                $extFind = true;
                            }
                        }
                        if(!$extFind) {
                            continue;
                        }
                    }
                    $list[is_file($filePath) ? $fileInfo['filename'] : $file] = $this->getFiles($path . $file . (is_file($filePath) ? '' : '/'), $exts, $getContents);
                }
            }
            return $list;
        }
        return $getContents ? str_replace("\n", '<br>', file_get_contents($path)) : str_replace(HOST_PATH, HOST, $path);
    }
    public function apiCalendarConstructorTexts()
    {
        return $this->getFiles(CONTENT_PATH, [
            'txt'
        ]);
    }
    public function apiCalendarConstructorImages()
    {
        return $this->getFiles(CALENDAR_IMAGES_PATH, [
            'jpg',
            'jpeg',
            'png'
        ], false);
    }
    public function apiCalendarConstructorSheetRemove()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $calendar = $this->query("SELECT * FROM calendar_constructor WHERE id=?", intval($this->param('id')))->fetch_assoc();
        if(!$calendar){
            return $this->error('Sheet not found');
        }
        if($calendar['user_id'] !== $user['id']){
            return $this->error('No rights');
        }
        if(!$this->query("DELETE FROM calendar_constructor WHERE id=?", $calendar['id'])){
            return $this->error('Unknown error');
        }
        return 'Sheet removed';
    }
    public function apiCalendarConstructorSheetEdit()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $id = $this->paramId('id');
        $name = $this->param('name');
        if(!mb_strlen($name) || mb_strlen($name) > 30){
            return $this->error('Sheet name must be from 1 to 30 characters');
        }
        $data = @json_decode($this->param('data'), true);
        if(!is_array($data)){
            return $this->error('Incorrect data');
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $calendar = $this->query("SELECT * FROM calendar_constructor WHERE id=?", $id)->fetch_assoc();
        if(!$calendar){
            return $this->error('Sheet not found');
        }
        if($calendar['user_id'] !== $user['id']){
            return $this->error('No rights');
        }
        $this->query("UPDATE calendar_constructor SET data=?, name=? WHERE id=?", $data, $name, $id);
        return true;
    }
    public function apiCalendarConstructorSheetAdd()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $name = $this->param('name');
        if(!mb_strlen($name) || mb_strlen($name) > 30){
            return $this->error('Sheet name must be from 1 to 30 characters');
        }
        $data = @json_decode($this->param('data'), true);
        if(!is_array($data)){
            return $this->error('Incorrect data');
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        if(!$this->query("INSERT INTO calendar_constructor (user_id, data, created, name) VALUES (?,?,?,?)", $user["id"], $data, time(), $name)){
            return $this->error('Unknown error');
        }
        $calendar = $this->query("SELECT * FROM calendar_constructor WHERE id=?", $this->db->insert_id)->fetch_assoc();
        if(!$calendar){
            return $this->error('Unknown error');
        }
        $calendar['data'] = @json_decode($calendar['data'], true);
        $calendar['created'] = date('d.m.Y H:i:s', $calendar['created']);
        return $calendar;
    }
    public function apiGetCalendarConstructorSheets()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $calendars = $this->query("SELECT * FROM calendar_constructor WHERE user_id=?", $user['id']);
        $list = [];
        while($calendar = $calendars->fetch_assoc()){
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
        if(!is_array($user)){
            return $this->error($user);
        }
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            return $this->error('Incorrect email');
        }
        if(mb_strlen($email) > 30){
            return $this->error('Email must be to 30 characters');
        }
        $code = rand(100000, 999999);
        if(!$this->query("UPDATE users SET email_code=?, email_code_expires=? WHERE id=?", $code, time() + CONFIRM_CODE_EXPIRES, $user['id'])){
            return $this->error('Unknown error');
        }
        $this->sendEmailCode($user['email'], $code);
        return 'Email change confirmation code sent to old email';
    }
    public function apiChangeEmail()
    {
        $email = $this->param('email');
        $code = intval($this->param('code'));
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            return $this->error('Incorrect email');
        }
        if(mb_strlen($email) > 30){
            return $this->error('Email must be to 30 characters');
        }
        if(time() > $user['email_code_expires']){
            return $this->error('Confirm code expired');
        }
        if($user['email_code'] != $code){
            return $this->error('Incorrect confirm code');
        }
        if($email == $user['email']){
            return $this->error('Email is the same as the previous email');
        }
        if(!$this->query("UPDATE users SET email=? WHERE id=?", $email, $user['id'])){
            return $this->error('Unknown error');
        }
        return 'Email changed';
    }
    public function apiChangePassword()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $password = Functions::aesDecrypt($this->param('password'));
        if(mb_strlen($password) < 6 || mb_strlen($password) > 15){
            return $this->error('Password must be from 6 to 15 characters');
        }
        if(password_verify($password, $user['password'])){
           return $this->error('Password is the same as the previous password');
        }
        $password = password_hash($password,PASSWORD_DEFAULT);
        if(!$this->query("UPDATE users SET password=? WHERE id=?", $password, $user['id'])){
            return $this->error('Unknown error');
        }
        return 'Password changed';
    }
    public function apiProfileRemoveAvatar()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $this->query("UPDATE users SET photo=? WHERE id=?", '', $user['id']);
        return 'Avatar removed';
    }
    public function apiProfileSaveAvatar()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $photo = $this->param('photo');
        if(!file_exists(UPLOADS_PATH . $photo)){
            return $this->error('Image not found');
        }
        $this->query("UPDATE users SET photo=? WHERE id=?", $photo, $user['id']);
        return UPLOADS_LINK . $photo;
    }
    public function apiGetProfile()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $user['photo'] = $user['photo'] ? UPLOADS_LINK . $user['photo'] : '';
        return $this->fields($user, [
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
    }
    public function apiSetProfile()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        $name = $this->param('name');
        $surname = $this->param('surname');
        $interests = $this->param('interests');
        $login = $this->param('login');
        $about = $this->param('about');
        $gender = intval($this->param('gender'));
        $week_type = $this->param('week_type');
        if($week_type !== null){
            $this->query("UPDATE users SET week_type=? WHERE id=?", $week_type ? 1 : 0, $user['id']);
            return 'Week type saved';
        }
        if($name != '' && (mb_strlen($name) < 2 || mb_strlen($name) > 15)){
            return $this->error('Name must be from 2 to 15 characters');
        }
        if(mb_strlen($name) && preg_match('/[^A-z\d]/', $name)){
            return $this->error('Name must be of Latin letters and Arabic numbers');
        }
        if($surname != '' && (mb_strlen($surname) < 2 || mb_strlen($surname) > 15)){
            return $this->error('Last Name must be from 2 to 15 characters');
        }
        if(mb_strlen($surname) && preg_match('/[^A-z\d]/', $surname)){
            return $this->error('Last Name must be of Latin letters and Arabic numbers');
        }
        if(mb_strlen($interests) && preg_match('/[^A-z\d]/', $interests)){
            return $this->error('Most interested in teaching must be of Latin letters and Arabic numbers');
        }
        if(mb_strlen($interests) > 100){
            return $this->error('Most interested in teaching must be to 100 characters');
        }
        if(count(explode(" ", $about)) > 100){
            return $this->error('About yourself should not have more than 100 words');
        }
        if(mb_strlen($about) && preg_match('/[^A-z\d]/', $about)){
            return $this->error('About yourself must be of Latin letters and Arabic numbers');
        }
        if(mb_strlen($about) > 1000){
            return $this->error('About yourself must be to 1000 characters');
        }
        if(!in_array($gender, [0, 1, 2])){
            return $this->error('Incorrect gender');
        }
        if(mb_strlen($login) && preg_match('/[^A-z\d]/', $login)){
            return $this->error('Login must be of Latin letters and Arabic numbers');
        }
        $userFromLogin = $this->query("SELECT * FROM users WHERE login=? AND id <> ?", $login, $user['id'])->fetch_assoc();
        if($userFromLogin){
            return $this->error('Login busy');
        }
        $this->query("UPDATE users SET name=?,surname=?,interests=?,login=?,about=?,gender=? WHERE id=?", $name, $surname, $interests, $login, $about, $gender, $user['id']);
        return 'General information saved';
    }
    public function apiVerify()
    {
        $code = $this->param('code');
        $user = $this->query("SELECT * FROM users WHERE verify_code=?", $code)->fetch_assoc();
        if(!$user){
            return $this->error("User not found");
        }
        if(time() > $user['verify_code']){
            return $this->error("Verify code expired. To receive a new activation code, log in");
        }
        if($user['verify_code'] !== $code){
            return $this->error('Incorrect verify code');
        }
        if(!$this->query("UPDATE users SET verify=1 WHERE id=?", $user['id'])){
            return $this->error('Unknown error');
        }
        return 'Account has been confirmed, you are now logged in to your account.';
    }
    public function apiRecovery()
    {
        $email = $this->param('email');
        $user = $this->query("SELECT * FROM users WHERE email=?", $email)->fetch_assoc();
        if(!$user){
            return $this->error('User not found');
        }
        if(!$user['verify']){
            return $this->error('Account not verified');
        }
        $newPassword = uniqid();
        if(!$this->query("UPDATE users SET recovery_password=? WHERE id=?", password_hash($newPassword, PASSWORD_DEFAULT), $user['id'])){
            return $this->error('Unknown error');
        }
        $this->sendPassword($user['email'], $newPassword);
        return 'New password has been generated and sent by email';
    }
    public function apiSignout()
    {
        $user = $this->session();
        if(!is_array($user)){
            return $this->error($user);
        }
        if(!$this->query("DELETE FROM sessions WHERE id=?", $user['session']['id'])){
            return $this->error('Session not found');
        }
        $_SESSION['admin'] = 0;
        return 'Session destroyed';
    }
    public function apiSignin()
    {
        $email = $this->param('email');
        $password = Functions::aesDecrypt($this->param('password'));
        $user = $this->query("SELECT * FROM users WHERE email=?", $email)->fetch_assoc();
        $clientId = Functions::getClientToken();
        if(!$user){
            return $this->error('User not found');
        }
        if($user['recovery_password'] && password_verify($password, $user['recovery_password'])){
            $this->query("UPDATE users SET password=recovery_password,recovery_password='' WHERE id=?", $user['id']);
        }elseif(!password_verify($password, $user['password'])){
            return $this->error('Incorrect email or password');
        }
        if(!$user['verify']){
            $verifyCode = md5(Functions::getClientToken() . uniqid('calendar_') . microtime(1));
            $verifyCodeExpires = time() + VERIFY_LINK_EXPIRES;
            if(!$this->query("UPDATE users SET verify_code=?,verify_code_expires=? WHERE id=?", $verifyCode,  $verifyCodeExpires, $user['id'])){
                return $this->error('Unknown error');
            }
            $this->sendVerifyCode($user['email'], $verifyCode);
            return $this->error('Account not verified, we resent a message with a link to confirm your account to your email');
        }
        $token = md5($user['id'] . $clientId . uniqid('calendar_') . microtime(1));
        $this->query("DELETE FROM sessions WHERE client=?", $clientId);
        if(!$this->query("INSERT INTO sessions (user_id,client,token,expires) VALUES (?,?,?,?)", $user['id'], $clientId, $token, time() + SESSION_EXPIRES)){
            return $this->error('Unknown error');
        }
        $session = $this->query("SELECT * FROM sessions WHERE id=?", $this->db->insert_id)->fetch_assoc();
        if(!$session){
            return $this->error('Unknown error');
        }
        $_SESSION['admin'] = $user['admin'];
        return $session['id'] . '_' . $session['token'];
    }
    public function apiSignup()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $email = $this->param('email');
        $password = Functions::aesDecrypt($this->param('password'));
        $name = $this->param('name');
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            return $this->error('Incorrect email');
        }
        if(mb_strlen($email) > 30){
            return $this->error('Email must be to 30 characters');
        }
        if(mb_strlen($password) < 6 || mb_strlen($password) > 15){
            return $this->error('Password must be from 6 to 15 characters');
        }
        if(mb_strlen($name) < 2 || mb_strlen($name) > 15){
            return $this->error('Name must be from 2 to 15 characters');
        }
        if(preg_match('/[^A-z\d]/', $name)){
            return $this->error('Name must be of Latin letters and Arabic numbers');
        }
        $user = $this->query("SELECT * FROM users WHERE email=?", $email)->fetch_assoc();
        if($user){
            return $this->error('Email busy');
        }
        $password = password_hash($password,PASSWORD_DEFAULT);
        $verifyCode = md5(Functions::getClientToken() . uniqid('calendar_') . microtime(1));
        $verifyCodeExpires = time() + VERIFY_LINK_EXPIRES;
        if(!$this->query("INSERT INTO users (email,password,name,created,verify_code,verify_code_expires) VALUES (?,?,?,?,?,?)", $email, $password, $name, time(), $verifyCode, $verifyCodeExpires)){
            return $this->error('Unknown error');
        }
        $this->sendVerifyCode($email, $verifyCode);
        return 'An email has been sent to your email with link to activate your account. Follow it and you will be able to successfully log in to the site. The link is valid for an hour.';
    }
    public function query()
    {
        return call_user_func_array([$this->db, 'query'], func_get_args());
    }
    public function param($name, $default = null)
    {
        if($default !== null && !isset($_POST[$name])){
            $_POST[$name] = $default;
        }
        return isset($_POST[$name]) ? $_POST[$name] : '';
    }
    public function paramId($name)
    {
        $id = intval($this->param($name));
        return $id < 0 ? 0 : $id;
    }
    public function paramIds($name)
    {
        $list = [];
        foreach(explode(",", $this->param($name)) as $id){
            $id = intval($id);
            if($id > 0) {
                $list[$id] = true;
            }
        }
        return array_keys($list);
    }
    public function handle()
    {
        $method = 'api' . (isset($_POST['method']) ? $_POST['method'] : '');
        $response = $this->error('Method not found');
        if(isset($_POST['method']) && method_exists($this, $method)){
            $response = call_user_func([$this, $method]);
            if(!is_array($response) || !isset($response['error'])){
                $response = [
                    'success' => $response
                ];
            }
        }
        header('Content-Type: application/json; charset=utf-8;');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    public function uploadFile()
    {
        $user = $this->session();
        $response = $this->error('Unknown error');
        if(!is_array($user)){
            $response = $this->error($user);
        }else{
            if(isset($_FILES['photo'])){
                $photo = $_FILES['photo'];
                $imageData = file_get_contents($photo['tmp_name']);
                $image = imagecreatefromstring($imageData);
                if(!$image){
                    $response = $this->error('Incorrect image format');
                }else{
                    $percent = 0.25;
                    $fileName = md5($imageData) . '.png';
                    $imageWidth = imagesx($image);
                    $imageHeight = imagesy($image);
                    $newImageWidth = ceil($imageWidth * $percent);
                    $newImageHeight = ceil($imageHeight * $percent);
                    $newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
                    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newImageWidth, $newImageHeight, $imageWidth, $imageHeight);
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
    private function fields(array $data, array $on = [], array $off = [])
    {
        if(count($off)){
            $on = [];
        }
        $arr = [];
        if(count($on)){
            foreach($on as $v){
                $arr[$v] = isset($data[$v]) ? $data[$v] : '';
            }
        }
        if(count($off)){
            $arr = $data;
            foreach($off as $v){
                if(isset($arr[$v])){
                    unset($arr[$v]);
                }
            }
        }
        return $arr;
    }
    private function error($text)
    {
        return [
            'error' => $text
        ];
    }
    private function sendVerifyCode($email, $verifyCode)
    {
        Functions::mail($email, 'Account activation', 'Account activation link:<br><a href="'.HOST . '?verify=' . $verifyCode .'">Confirm account</a>');
    }
    private function sendPassword($email, $password)
    {
        Functions::mail($email, 'Account recovery', 'Account new password:<br>' . $password);
    }
    private function sendEmailCode($email, $code)
    {
        Functions::mail($email, 'Account confirm', 'Confirm code:<br>' . $code);
    }
    private function session($isAdmin = false)
    {
        $token = $this->param('token', '');
        $tokenExplode = explode('_', $token);
        if(count($tokenExplode) !== 2){
            return 'Incorrect token';
        }
        list($sessionId,$sessionIoken) = $tokenExplode;
        $session = $this->query("SELECT * FROM sessions WHERE id=?", intval($sessionId))->fetch_assoc();
        if(!$session || $session['token'] !== $sessionIoken || $session['client'] !== Functions::getClientToken()){
            return 'Session not found';
        }
        if(time() > $session['expires']){
            return 'Session expired';
        }
        $user = $this->query("SELECT * FROM users WHERE id=?", $session['user_id'])->fetch_assoc();
        if(!$user){
            return 'Session not found';
        }
        if($isAdmin && !$user['admin']){
            return 'No admin rights';
        }
        $user['session'] = $session;
        return $user;
    }
}
