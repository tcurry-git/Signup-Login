<?php
// THINGS TO DO: 
// ERROR HANDLING - try catch errors g into log, user errors dont, security errors (lots of failed login attempts and password resets e.g. log IP and timestamp) go into auditing log file
// GUARDING PAGES LIKE WE DID THIS ONE
// CONNECT MAIL SERVER
// FORM INPUT AND BUTTON SIZING
session_start();
date_default_timezone_set('Europe/London'); 
include("dbConnect.php");
// if (!isset($_SESSION["successfully_verified_user"]) || !isset($_SESSION["user_verification_email"])) {
//     header("Location: verifyIdentity.php"); // guards the page so users can't just type in the page url to access this page
//     exit;
// }

if (isset($_SESSION["successfully_verified_user"])){
    echo"<div class='success_alert'><img src='success.png' width=20px height=20px>&nbsp;<p>{$_SESSION["successfully_verified_user"]}</p></div>";
    unset($_SESSION["successfully_verified_user"]);
}
$forgot_password_errors = [];

if (($_SERVER["REQUEST_METHOD"] === "POST") && isset($_POST["submit_new_password"])){
    $new_password = $_POST["new_password"];
    $new_password_verify = $_POST["verify_new_password"] ?? ''; // prevents undefined array key (array can't be null but it can be empty)
    
    //validation
    if (empty($new_password) || 
        strlen($new_password) < 8 ||
        strlen($new_password) > 40 ||
        !preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).*$/", $new_password)){
            $forgot_password_errors[] = "Please ensure your password has between 8-40 characters, has at least one symbol, a capital letter, and a number";
        }
    if ($new_password !== $new_password_verify || empty($new_password_verify)){
        $forgot_password_errors[] = "Password mismatch";
    }


    if (empty($forgot_password_errors)) {
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_change_password = $pdo->prepare("UPDATE users SET password = :new_password WHERE email = :user_email");
        $stmt_change_password->execute([
        ":new_password" => $hashed_new_password,
        ":user_email" => $_SESSION["user_verification_email"]
        ]);
    
        if ($stmt_change_password->rowCount() > 0){
            echo"<div class='success_alert'><img src='success.png' width=20px height=20px>&nbsp;<p>We have successfully changed your password, you'll be automatically redirected to the Log In page in 5 seconds</p></div>";
            unset($_SESSION["successfully_verified_user"]);
            unset($_SESSION["user_verification_email"]);
            $_SESSION["successfully_changed_password"] = true;
        } else {
           echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px>&nbsp;<p>Warning: User doesn't exist</p></div></div>";
        }
    }
   
     if (!empty($forgot_password_errors)){
       echo"<div class='errors_container'>";
        foreach($forgot_password_errors as $key => $error){
            echo"<div class='error'><img src='warning.png' width=20px height=20px><p>{$error}</p><br><br></div>";
        }
        echo"</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script> <!--defer means run after your html -->
</head>
<body>
<?php if (isset($_SESSION["successfully_changed_password"])): ?>
<meta http-equiv="refresh" content="6;url=login.php">
<!--  //isset($_SESSION["successfully_changed_password"]) ? 'disabled class="form_disabled"' : '' ?>  -->
<?php endif; ?>

    <header class="navigation_header">
            <nav>
                <a class="rth" href="index.php">< Return to Home</a>
            </nav>
        </header>
    <div class="fp_container">
        <!-- <div class="fp_form_container"> -->
            <h1 class="form_heading">Create a <span class="heading_highlight">New Password</span> for <br>your account</h1>
            <form action="" class="fp_form" method="post">
            <!-- <fieldset> -->
                    <div class="form_group">
                        <input type="password" id="new_password" class="password_input input_field" name="new_password">
                        <label for="new_password">New password <span class="required_asterisk">*</span></label>
                    </div>
                    <div class="form_group">
                        <input type="password" id="verify_new_password" class="password_verify_input input_field" name="verify_new_password">
                        <label for="verify_new_password">Verify new password <span class="required_asterisk">*</span></label>
                    </div>
                    <div class="form_group">
                        <input type="submit" name="submit_new_password" value="Change Password">
                    </div>
                <!-- </fieldset>  -->
            </form>
        <!-- </div> -->
    </div>
</body>
</html>