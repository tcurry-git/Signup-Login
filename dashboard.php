<?php
// EMAIL FIELD ISNT STAYING SET WHEN THE CODE GETS SENT....
session_start();
date_default_timezone_set('Europe/London'); 
include("dbConnect.php");
// if (!isset($_SESSION["successful_login"]) &&
//     !isset($_SESSION["successful_login_remember"]) &&
//     !isset($_SESSION["successful_login_no_remember"]) &&
//     !isset($_SESSION["user_id"])){ // guarding the page
//     header("Location: login.php");
//     exit;
// } 
if (isset($_SESSION["successful_login"])){
    echo"<div class='success_alert'>
        <img src='success.png' alt='success_tick' width=20px height=20px>{$_SESSION["successful_login"]}
    </div>";
}
if (isset($_SESSION["successful_login_remember"])){
    echo"<div class='success_alert'>
        <img src='success.png' alt='success_tick' width=20px height=20px>&nbsp;{$_SESSION["successful_login_remember"]}
    </div>";
    unset($_SESSION["successful_login_remember"]);//unset this because otherwise the user always remains logged in (technically) so auto login never works
} elseif (isset($_SESSION["successful_login_no_remember"])){
     echo"<div class='success_alert'>
        <img src='success.png' alt='success_tick' width=20px height=20px>&nbsp;{$_SESSION["successful_login_no_remember"]}
    </div>";
    unset($_SESSION["successful_login_no_remember"]);
}
if (isset($_SESSION["user_id"]) || $_SESSION["successful_login"]){
    try {
        $stmt_get_username = $pdo->prepare("SELECT username FROM users WHERE ID = :user_id LIMIT 1");
        $stmt_get_username->execute([":user_id" => $_SESSION["user_id"]]);
        $personalized_dash_header_username = $stmt_get_username->fetch(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
            $error_log_file =  __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
            $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
            error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
    }
 // FETCH_ASSOC return associative array like ["username" => "Taylor"] column is just the username
    if ($personalized_dash_header_username){
        echo"<div class='dash_container'>
            <h1 class='form_heading'>This is <span class='heading_highlight'>{$personalized_dash_header_username}</span>'s dashboard</h1>
            <form action='' method='post'>
                <button class='logout_btn button' name='logout'>Log Out</button>
            </form>
        </div>";
    }

    // unset($_SESSION["personlized_dash_header"]); //dont need to unset this because we want to stay permanently visible to the user
}
    unset($_SESSION["user_id"]);

if (isset($_POST["logout"])){
    if (isset($_COOKIE["remember_me"])){
        $saved_token = $_COOKIE["remember_me"];
        $hashed_saved_token = hash('sha256', $saved_token);
        try {
            $stmt_delete_stored_token = $pdo->prepare("DELETE FROM user_cookie WHERE hashed_token = :saved_hash_token LIMIT 1");
            $stmt_delete_stored_token->execute([":saved_hash_token" => $hashed_saved_token]);
        } catch (PDOException $e) {
            $error_log_file =  __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
            $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
            error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
        }
            setcookie("remember_me", "", time() - 9999999, "/");
            unset($_COOKIE["remember_me"]);
    }
    // if (session_status() === PHP_SESSION_ACTIVE) {
    //     session_unset();
    //     session_destroy();
    // }
    unset($_SESSION["successful_login"]);
    header("Location: login.php");
    exit;
    // setcookie("successful_login", "", time() - 3600, "/"); // ADD THINGS LIKE CAN'T HAVE DUPLICATE USERNAMES, PHONE NUMBERS OR EMAILS...FORGOT PASSWORD...CHANGE UI OF FLATPICKER IF POSSIBLE
    // setcookie("remember_me", "", time() - 3600, "/");
    // header("Location: login.php"); // need to destroy the stored hashed token for the user on logout click..
    // exit;
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
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" async defer></script>
    <!-- <a href="https://www.flaticon.com/free-icons/warning" title="warning icons">Warning icons created by Fathema Khanom - Flaticon</a> -->
    <!-- <a href="https://www.flaticon.com/free-icons/approved" title="approved icons">Approved icons created by waqasshah - Flaticon</a> -->
</head>
<body>