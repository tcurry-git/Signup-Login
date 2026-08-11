<?php
session_start();
date_default_timezone_set('Europe/London'); 
include("dbConnect.php");

if (isset($_SESSION["successfully_changed_password"])){
    echo"<div class='success_alert'><img src='success.png' width=20px height=20px>&nbsp;You can now Log In with your new password</div>";
    unset($_SESSION["successfully_changed_password"]);
}

if (!isset($_SESSION["user_id"]) && isset($_COOKIE["remember_me"])){ //session is only there if user is logged in...so if the user is NOT logged in but remember me has been clicked..
    $token = $_COOKIE["remember_me"]; //set the token to the same as it was..
    $hashed_token = hash('sha256', $token);
    //get the row of the user_id to check
    try {
        $stmt_retrieve_user_cookie_row = $pdo->prepare("SELECT user_id, expiry_date FROM user_cookie WHERE hashed_token = :hashed_token LIMIT 1");
        $stmt_retrieve_user_cookie_row->execute([
            ":hashed_token" => $hashed_token
        ]);
        $user_cookie_row = $stmt_retrieve_user_cookie_row->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_log_file =  __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
        $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
        error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
    }

    if (strtotime($user_cookie_row['expiry_date']) > time()){
        try {
            $stmt_retrieve_username_cookie = $pdo->prepare("SELECT users.username
                                                        FROM user_cookie
                                                        JOIN users ON user_cookie.user_id = users.ID
                                                        WHERE user_cookie.hashed_token = :hashed_token
                                                        LIMIT 1");
        $stmt_retrieve_username_cookie->execute([":hashed_token" => $hashed_token]);
        $username_cookie = $stmt_retrieve_username_cookie->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_log_file =  __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
            $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
            error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
        }
      
        $_SESSION["user_id"] = $user_cookie_row["user_id"];
        $_SESSION["successful_login"] = "Welcome {$username_cookie['username']}, You have been successfully logged in!";
        // $_SESSION["personalized_dash_header"];
        header("Location: dashboard.php");
        exit;
    } else { //cookie has expired
        setcookie("remember_me", "", time() - 1, "/"); //remove the cookie
        $stmt_delete_hashed_token = $pdo->prepare("DELETE FROM user_cookie WHERE hashed_token = :hashed_token LIMIT 1");
        $stmt_delete_hashed_token->execute([
            ":hashed_token" => $hashed_token
        ]);
    }
}




if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["log_in_submit"])){
    $login_username = trim($_POST["log_in_username"]);
    $login_password = trim($_POST["log_in_password"]);
    $remember_me = $_POST["remember_me_checkbox"] ?? ''; //if its not selected its just blank so the variable isnt null
    $token_length = 32;
// login validation logic
    $log_in_errors = [];
;
    if (empty($login_username) || 
        strlen($login_username) < 4 ||
        strlen($login_username) > 25 ||
        !preg_match("/^[A-Za-z0-9_]{4,25}$/", $login_username)){
        $log_in_errors[] = "username must not be blank and must be between 4-25 characters";
    
    }   
    if (empty($login_password)) {
        $log_in_errors[] = "password must not be blank";
    }   

    if(empty($log_in_errors)){
        try {
            $stmt_retrieve_user_row = $pdo->prepare("SELECT * FROM users WHERE username = :login_query_username LIMIT 1");
            $stmt_retrieve_user_row->execute([":login_query_username" => $login_username]);
            $user = $stmt_retrieve_user_row->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_log_file =  __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
            $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
            error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
        }
       
        if ($user){ //if user exists...
            if (password_verify($login_password, $user['password'])){
                $user_id = $user['ID'];
                $_SESSION["user_id"] = $user_id; //need to make sure the browser knows the user is logged in on all pages
                // remember me   
                if (!empty($remember_me)){
                $token = bin2hex(random_bytes($token_length));
                setcookie(
                    "remember_me",
                    $token, 
                    time() + (86400*30),
                    "/", 
                    "", 
                    false, //secure
                    true // http only
                );

                $hashed_token = hash("sha256", $token); //hash for security
                $login_cookie_expiry = date("Y-m-d H:i:s", time() + (86400*30));
                try {
                $stmt_store_hashed_token = $pdo->prepare("INSERT INTO user_cookie (user_id, hashed_token, expiry_date)
                                        VALUES (:ID, :token, :login_cookie_expiry)");
                $stmt_store_hashed_token->execute([
                    ":ID" => $user_id,
                    ":token" => $hashed_token,
                    ":login_cookie_expiry" => $login_cookie_expiry
                ]);
                } catch (PDOException $e) {
                    $error_log_file =  __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
                    $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
                    error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
                }
               
                
                $_SESSION["successful_login_remember"] = "Welcome {$user['username']}, You have been successfully logged in! We will now auto log you in to your dashboard until you click 'Log Out'";
                // $_SESSION["personalized_dash_header"] = "This is <span class='heading_highlight'>{$user['username']}</span>'s dashboard";
                header("Location: dashboard.php");
                exit;
                                     
                } elseif (empty($remember_me)){
                    $_SESSION["successful_login_no_remember"] = "Welcome {$user['username']}, You have been successfully logged in! You will have to log in manually next time as you didn't click 'Remember me'";
                    // $_SESSION["personalized_dash_header"] = "This is <span class='heading_highlight'>{$user['username']}</span>'s dashboard";
                    header("Location: dashboard.php");
                    exit;
                }
               
            } else {
                echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px>&nbsp;<p>Warning: Password is incorrect</p></div></div>";
            } 
        } else {
            echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px>&nbsp;<p>Warning: Account details don't exist</p></div></div>";
        }
    }
    if (!empty($log_in_errors)){
        echo"<div class='errors_container'><div class='error'><img src='warning.png' width=20px height=20px>&nbsp;<p>Warning: Invalid credentials, please try again.</p></div></div>";
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
    <title>Log In</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <!-- <a href="https://www.flaticon.com/free-icons/warning" title="warning icons">Warning icons created by Fathema Khanom - Flaticon</a> -->
    <!-- <a href="https://www.flaticon.com/free-icons/approved" title="approved icons">Approved icons created by waqasshah - Flaticon</a> -->
</head>
<body>
  <?php
if (isset($_SESSION["account_created"])){
    echo"<div class='success_alert'>
            <img src=success.png alt='success tick' width=20px height=20px> &nbsp; {$_SESSION["account_created"]}
        </div>";
    unset($_SESSION["account_created"]);
}
if (isset($_SESSION["login_error"])){
    echo"<div class='incorrect_alert'>
            <img src=warning.png alt='warning symbol' width=20px height=20px> &nbsp; {$_SESSION['login_error']}
        </div>";
    unset($_SESSION["login_error"]);
}
if (isset($_SESSION["user_not_found"])){
    echo"<div class='incorrect_alert'>
            <img src=warning.png alt='warning symbol' width=20px height=20px> &nbsp; {$_SESSION['user_not_found']}
        </div>";
    unset($_SESSION["user_not_found"]);
}
?>
        <header class="navigation_header">
            <nav>
                <a class="rth" href="index.php">< Return to Home</a>
            </nav>
        </header>
    <div class="log_in_container">
        <div class="log_in_form_container">
            <h1 class="form_heading log_in_form_heading">Already have an account with us? <br> Lets get you <span class="heading_highlight">Logged In</span></h1>
            <form action="" class="log_in_form" method="post">
                <div class="log_in_form_group form_group">
                    <input type="text" name="log_in_username" id="log_in_username" class="username_input input_field" placeholder="Your username" maxlength="50">
                    <label for="log_in_username">Username <span class="required_asterisk">*</span></label>
                </div>
                <div class="log_in_form_group form_group">
                    <input type="password" name="log_in_password" id="log_in_password" class="password_input input_field" placeholder="Your password" minlength="8" maxlength="40">
                    <label for="log_in_password">Password <span class="required_asterisk">*</span></label>
                </div>
                <div class="remember_me_forgot_password_group">
                    <div class="remember_me">
                        <input type="checkbox" name="remember_me_checkbox" id="remember_me_checkbox">
                        <label for="remember_me_checkbox">Remember me</label>
                    </div>
                    <div class="forgot_password">
                        <a href="verifyIdentity.php">Forgot Password?</a>
                    </div>
                </div>
                <div class="log_in_form_group form_group">
                    <input class="button" type="submit" name="log_in_submit" value="Log In">
                </div>
            </form>
        </div>
    </div>

</body>
</html>
