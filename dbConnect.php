<?php
$dsn = "mysql:host=127.0.0.1;dbname=test_createaccount;charset=utf8mb4";
$db_username = "root";
$db_password = "";

try {
    $pdo = new PDO($dsn, $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage(); // SAVE TO ERROR LOG FILE
    $error_log_file = __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
    $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
    error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
    exit;
}


?>

