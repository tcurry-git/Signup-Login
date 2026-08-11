<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Manually require the files instead of using vendor/autoload.php
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// if (!isset($_SESSION["account_created"])){ // GUARDING THIS BECAUSE THERE'S NO REASON TO TRY AND RESET PASSWORD IF YOU DON'T HAVE AN ACCOUNT
//     header("Location: login.php");
//     unset($_SESSION["account_created"]);
//     exit;
// }
session_start();
include("dbConnect.php");
// if (!isset($_SESSION["account_created"])){
//     header("Location: login.php");
//     exit;
// }
date_default_timezone_set('Europe/London'); 
if (isset($_POST["submit_verify_id_email"])){
    $verify_id_email = trim($_POST["verify_id_email"]);
    $verify_id_email_errors = [];
    if (empty($verify_id_email) ||
        strlen($verify_id_email) < 11 || // min is a@gmail.com
        strlen($verify_id_email) > 50 ||
        !preg_match("/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.co\.uk|outlook\.com|outlook\.co\.uk)$/", $verify_id_email)) {
        $verify_id_email_errors[] = "Please ensure your email provider is one of the following: Gmail, Yahoo, Outlook and is less than 50 characters long";
        $_SESSION["verify_id_email_errors"] = $verify_id_email_errors;
    }
    if (empty($verify_id_email_errors)){
        try{
            $stmt_verify_email_exists = $pdo->prepare("SELECT email FROM users WHERE email = :verify_email LIMIT 1");
            $stmt_verify_email_exists->execute([":verify_email" => $verify_id_email]);
            $email_exists = $stmt_verify_email_exists->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error_log_file =  __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
            $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
            error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
        }
        if ($email_exists) {
            // generating a code and storing the credentials within sessions for later
            $verification_code = (string) random_int(100000, 999999); // now parses this integer as a string
            $_SESSION['verification_code'] = $verification_code;
            $_SESSION['verification_email'] = $verify_id_email;
            $_SESSION['verification_code_expires'] = time() + 600; // 10 minute expiration window
            try {
                // actually sending the email
                $verification_code_mail = new PHPMailer(true);
                $verification_code_mail->isSMTP();
                //STMP server credentials
                $verification_code_mail->Host       = 'sandbox.smtp.mailtrap.io'; // Replace with your SMTP server host
                $verification_code_mail->SMTPAuth   = true;
                $verification_code_mail->Username   = '048b0254f5762f'; 
                $verification_code_mail->Password   = 'a67e5e7498eb88'; 
                $verification_code_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $verification_code_mail->Port       = 587;
                // Recipients
                $verification_code_mail->setFrom('noreply@mypracticesite.com', 'Create Account Project');
                $verification_code_mail->addAddress($verify_id_email); 
                // Content
                $verification_code_mail->isHTML(true); // sets message to html
                $verification_code_mail->Subject = 'Your Verification Code';
                $verification_code_mail->Body    = "Your verification code is: <b>{$verification_code}</b>. This code expires in 10 minutes.<br><br><i>Disclaimer: Do not share this code with anyone</i>";
                $verification_code_mail->AltBody = "Your verification code is: " . $verification_code;
                // sends email
                $verification_code_mail->send();
                $_SESSION["verification_code_successfully_sent"] = true;
                // header("Location: " . $_SERVER['PHP_SELF']);
                // exit;
            } catch (Exception $e) {
                echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px>Mail Error: we were not able to send the verification code to you.</div></div>";
                $error_log_file =  __DIR__ . "/logs/general_errors.log";// DIR is the current file directory
                $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
                error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
            }
     
        } else {
            echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px>&nbsp;<p>There is no account that exists for this email</p></div></div>";
        }
}
if (!empty($verify_id_email_errors)){
        echo"<div class='errors_container'>"; 
            foreach($verify_id_email_errors as $key => $error){
               echo"<div class='error'><img src='warning.png' width=20px height=20px><p>{$error}</p><br><br></div>";
            }
        echo"</div>";
    }
}


if (isset($_SESSION["verification_code_successfully_sent"]) && isset($_POST["submit_verify_id_form"])) {
    $verification_code_input = trim($_POST["verification_code"]);
    $verification_email_input = trim($_POST["verify_id_email"]);
        if (isset($_SESSION["verification_code"]) && $verification_email_input === $_SESSION["verification_email"] &&
            $verification_code_input == $_SESSION["verification_code"]  &&
            time() < $_SESSION["verification_code_expires"]) {
                $_SESSION["user_verification_email"] = $_SESSION["verification_email"];
                $_SESSION["successfully_verified_user"] = "We have successfully confirmed who you are. You can now create a new password.";
                unset($_SESSION['verification_code']);
                unset($_SESSION['verification_email']);
                unset($_SESSION['verification_code_expires']);
                unset($_SESSION["verification_code_successfully_sent"]);
                header("Location: createNewPassword.php");
                exit;
            } else  {
            echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px><p>User error: verification code mismatch or you have waited too long to enter your verification code.</p></div></div>"; 
            }
} elseif (!isset($_SESSION["verification_code_successfully_sent"]) && isset($_POST["submit_verify_id_form"])) {
     echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px>&nbsp;<p>Invalid credentials. Please enter your email address so we can send you a verification code.</p></div></div>";
}
// if (time() > 0 && isset($_SESSION["successfully_verified_user"]) ){ // THE EMAIL WILL STAY IF SEND CODE BUT NOT SUBMIT FORM
//     unset($_SESSION['verification_code']);
//     unset($_SESSION['verification_email']);
//     unset($_SESSION['verification_code_expires']);
//     unset($_SESSION["verification_code_successfully_sent"]);
// }
// if (isset($_POST["submit_verify_id_form"]) && empty($_SESSION["verification_code_successfully_sent"])){
//          echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px><p>We weren't able to verify your identity. Ensure you have entered your email so we can send you a verification code</p></div></div>";
// }  
    
// isset($_SESSION['verification_code_successfully_sent']) ? 'readonly' : ''
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script> <!--defer means run after your html -->
    <!-- <a href="https://www.flaticon.com/free-icons/send" title="send icons">Send icons created by Pixel perfect - Flaticon</a> -->
</head>
<main>
    <header class="navigation_header">
            <nav>
                <a class="rth" href="index.php">< Return to Home</a>
            </nav>
        </header>
    <div class="verify_id_container">
        <div class="verify_id_form_container">
            <h1 class="form_heading verify_id_heading">We need to <span class="heading_highlight">Verify</span> who you are. Enter your email and we'll send you a <span class="heading_highlight">Verification Code</span></h1>
                <form action="" class="verify_id_form" method="POST">
                    <div class="verify_id_email_send_code">
                        <div class="form_group">
                            <input type="email" class="email_input input_field" id="verify_id_email" name="verify_id_email" placeholder="john.doe@[emailprovider].com"
                            value="<?= isset($_SESSION['verification_email']) ? htmlspecialchars($_SESSION["verification_email"]) : '' ?>">
                            <label for="verify_id_email">Email <span class="required_asterisk">*</span></label>
                        </div>
                        <button class="send_verification_code_button" id="submit_verify_id_email" name="submit_verify_id_email"><img src="send-arrow.png"></button>
                    </div>
                    <div class="form_group">
                        <input type="text" id="verification_code_input" class="input_field" name="verification_code" placeholder="e.g. 064591" maxlength="6">
                        <label for="verification_code_input">Verification Code <span class="required_asterisk">*</span></label>
                    </div>
                    <input class="button" type="submit" name="submit_verify_id_form" value="Verify Identity">
                </form>
        </div>
    </div>
</main>
<body>