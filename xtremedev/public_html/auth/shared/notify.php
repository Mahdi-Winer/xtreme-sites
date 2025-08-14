<?php
// shared/notify.php

require_once __DIR__ . '/../php-mailer/src/PHPMailer.php';
require_once __DIR__ . '/../php-mailer/src/SMTP.php';
require_once __DIR__ . '/../php-mailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * ارسال ایمیل با PHPMailer
 * @param string $to ایمیل مقصد
 * @param string $subject موضوع
 * @param string $body متن (HTML)
 * @param string $from ایمیل فرستنده (اختیاری)
 * @param string $fromName نام فرستنده (اختیاری)
 * @return bool|string true یا متن خطا
 */
function send_email($to, $subject, $body, $from = 'auth@xtremedev.co', $fromName = 'XtremeDev') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'mail.xtremedev.co';
        $mail->SMTPAuth = true;
        $mail->Username = 'auth@xtremedev.co';
        $mail->Password = 'B[qdn0*yHgppzQaf';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

/**
 * ارسال پیامک (نمونه ساده، باید با API سرویس پیامک جایگزین شود)
 * @param string $mobile شماره موبایل
 * @param string $message متن پیام
 * @return bool
 */
function send_sms($mobile, $message) {
    // اینجا با سرویس پیامک واقعی جایگزین کن
    // فعلاً فقط لاگ می‌گیرد
    file_put_contents(__DIR__ . '/../sms_log.txt', date('Y-m-d H:i:s') . " TO $mobile: $message\n", FILE_APPEND);
    return true;
}