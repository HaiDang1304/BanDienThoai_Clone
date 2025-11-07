<?php
// app/helpers/Mailer.php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php'; 

class Mailer {
  public static function send(string $to, string $subject, string $html): bool {
    $mail = new PHPMailer(true);
    try {
      $mail->SMTPDebug = 0;
      $mail->CharSet = 'UTF-8';
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = '22029023@st.vlute.edu.vn';  
      $mail->Password   = 'xnwmplksnzalsvyl'; 
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = 587;

      $mail->setFrom('youremail@gmail.com', 'BanDienThoai Clone');
      $mail->addAddress($to);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $html;
      $mail->AltBody = strip_tags($html);

      return $mail->send();
    } catch (Exception $e) {
      error_log('MAILER: '.$mail->ErrorInfo);
      return false;
    }
  }
}
