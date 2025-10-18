<?php

namespace Calendar;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Functions
{
    public static function mail($email, $subject, $body)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Port       = SMTP_PORT;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Username = SMTP_USERNAME;

            $mail->CharSet = "UTF-8";
            $mail->Encoding = 'base64';

            $mail->setFrom($mail->Username, SMTP_FROM_NAME);
            $mail->addCC($mail->Username);
            $mail->addBCC($mail->Username);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            if (isset($db)) {
                $api = new Api($db, false);
                $details = get_defined_vars();
                $api->log('Fail on send e-mail', $details);
            }
            echo $e->getMessage();

            die;
        }
    }

    public static function redirect($url, $replace = false)
    {
        if ($replace) {
            header('Location: ' . $url, false, 301);
        } else {
            header('Location: ' . $url);
        }
    }

    public static function csrfToken($token = null)
    {
        if (is_string($token) && mb_strlen($token) == 32) {
            return isset($_REQUEST['csrf_token']) && $_REQUEST['csrf_token'] == $token;
        }
        return md5(uniqid('calender_') . microtime(1));
    }

    public static function aesDecrypt($text)
    {
        $json = json_decode((string)base64_decode($text), true);
        if (
            !is_array($json) ||
            !array_key_exists('salt', $json) ||
            !array_key_exists('iv', $json) ||
            !array_key_exists('text', $json) ||
            !array_key_exists('iterations', $json)
        ) {
            return null;
        }

        try {
            $salt = hex2bin($json['salt']);
            $iv = hex2bin($json['iv']);
        } catch (Exception $e) {
            return null;
        }

        $cipherText = base64_decode($json['text']);
        $iterations = intval(abs((int)$json['iterations']));
        if ($iterations <= 0) {
            $iterations = 999;
        }
        $hashKey = hash_pbkdf2('sha512', self::getClientToken(), $salt, $iterations, 64);
        unset($iterations, $json, $salt);
        $decrypted = openssl_decrypt($cipherText, 'AES-256-CBC', hex2bin($hashKey), OPENSSL_RAW_DATA, $iv);
        if (!is_string($decrypted)) {
            $decrypted = null;
        }
        unset($cipherText, $hashKey, $iv);
        return $decrypted;
    }

    public static function getClientToken()
    {
        return $_COOKIE['csrf_token'] ?? '';
    }
}
