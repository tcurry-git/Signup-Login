<?php
// FIX THE VALID INPUTS THEY ALL GET MESSED UP, THE INVALID/VALID COLORS ON INPUTS SHOULD BE FOR ALL INPUTS, CREATE CLASSES FOR EACH INPUT TYPE LIKE EMAIL, PASSWORD ETC
// THEN MAKE RESPONSIVE, NEED DRAINPIPES EITHER SIDE ON MOBILE, SIGN UP PAGE HAS TO BE SCROLLABLE SO NOT FIXED HEIGHT, HOME BUTTONS STACKED ON EACHOTHER
// **VALID INPUTS SHOULD STAY ON FORM REFRESH OR SUBMIT ON EVERY PAGE **//
//**GUARD CREATE NEW PASSWORD */
session_start();
date_default_timezone_set('Europe/London'); 
include("dbConnect.php");
if (isset($_COOKIE["remember_me"])){
    setcookie("remember_me", "", time() - 1000, "/"); //fix issue that I was being straight to dashboard after signing up? 
}

// Read session into local variable for HTML rendering
$valid_form_inputs = $_SESSION["valid_form_inputs"] ?? []; // the variables are already holding the sessions so unset doesnt affect these
$sign_up_errors = $_SESSION["sign_up_errors"] ?? [];
// Clear session keys so fresh manual reloads start clean
unset($_SESSION["valid_form_inputs"]);
unset($_SESSION["sign_up_errors"]);

if ($_SERVER["REQUEST_METHOD"] === "POST"){ //only need to sanitize data when you are outputting data to the page/browser
  // have to add trim again bc remember anyone could ater the JS 
    $signup_firstname = trim($_POST["sign_up_firstname"]);
    $signup_lastname = trim($_POST["sign_up_lastname"]);
    $signup_email = trim($_POST["sign_up_email"]);
    $signup_phone = trim($_POST["sign_up_phone_num"]);
    $signup_dob = trim($_POST["sign_up_dob"]);
    $signup_username = trim($_POST["sign_up_username"]);
    $signup_password = trim($_POST["sign_up_password"]);
    $signup_password_verify = trim($_POST["sign_up_password_verify"]); 

    $sign_up_errors = [];
    $valid_form_inputs = [];


    //fullname
    if (empty($signup_firstname) || 
        !preg_match("/^[A-Z][a-z]{1,19}$/", $signup_firstname)) { // must be a captial letter at the start, rest must be between 1-19
        $sign_up_errors[] = "Please ensure firstname starts with a capital letter and is 2-20 characters long";
    } elseif (preg_match("/^[A-Z][a-z]{1,19}$/", $signup_firstname)){
        $valid_form_inputs['firstname'] = $signup_firstname;
    }
    if (empty($signup_lastname) || 
        !preg_match("/^[A-Z][a-z]{1,19}$/", $signup_lastname)) {
        $sign_up_errors[] = "Please ensure lastname starts with a capital letter and is 2-20 characters long";
    } elseif (preg_match("/^[A-Z][a-z]{1,19}$/", $signup_lastname)){
        $valid_form_inputs['lastname'] = $signup_lastname;
    }
    //email can type any mail provider after the @
    if (empty($signup_email) || 
        !preg_match("/^(?=.{11,50}$)[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.co\.uk|outlook\.com|outlook\.co\.uk)$/", $signup_email)) { // ?=. is called a 'lookahead' is check to see if the full length of string beginning to end is 11-50
        $sign_up_errors[] = "Please ensure your email provider is one of the following: Gmail, Yahoo, Outlook and is less than 50 characters long";
    } elseif (preg_match("/^(?=.{11,50}$)[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.co\.uk|outlook\.com|outlook\.co\.uk)$/" , $signup_email)){
        $valid_form_inputs['email'] = $signup_email;
    }
    //phone
    if (empty($signup_phone) || 
        !preg_match("/^\+?[0-9]{10}$/", $signup_phone)) {
        $sign_up_errors[] = "Phone number must be 10 digits long (excluding country code)";
    } elseif (preg_match("/^\+?[0-9]{10}$/", $signup_phone)) {
        $valid_form_inputs['phone'] = $signup_phone;
    }

$signup_dob_mysql = null;
if (empty($signup_dob) || !preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/", $signup_dob)) {
    $sign_up_errors[] = "Date of birth isn't in the correct format. Must be dd/mm/yyyy";
} else {
    list($day, $month, $year) = explode("/", $signup_dob);

    if (!checkdate((int)$month, (int)$day, (int)$year)) {
        $sign_up_errors[] = "Date of birth is not a real date";
    } else {
        $dob = DateTime::createFromFormat("d/m/Y", $signup_dob);
        $today = new DateTime();
        $age = $today->diff($dob)->y;

        if ($age < 13) {
            $sign_up_errors[] = "You must be at least 13 years old";
        } else {
            $signup_dob_mysql = $dob->format("Y-m-d");
            $valid_form_inputs['dob'] = $signup_dob;
        }
    }
}
    
    //username regex should mean if not uppercase or lowercase letter, number and underscores only, reject
    $stmt_check_username = $pdo->prepare("SELECT ID FROM users WHERE username = :stored_username LIMIT 1");
    $stmt_check_username->execute(["stored_username" => $signup_username]);
    $stmt_user_with_username_exists = $stmt_check_username->fetch(PDO::FETCH_ASSOC);
    if ($stmt_user_with_username_exists) {
        $sign_up_errors[] = "Username was already taken";
    }

    if (empty($signup_username) || 
        !preg_match("/^[A-Za-z0-9_]{4,25}$/", $signup_username)){
        $sign_up_errors[] = "Please ensure your username is between 4-25 characters and only contains letters, numbers and symbols";
    } elseif (preg_match("/^[A-Za-z0-9_]{4,25}$/", $signup_username)){
        $valid_form_inputs['username'] = $signup_username;
    }
    //password eye thing should stay permanently
    if (empty($signup_password) || 
        !preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,40}$/", $signup_password)) {
        $sign_up_errors[] = "Please ensure your password is between 8-40 characters, has at least one symbol, a capital letter and a number";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,40}$/", $signup_password)) {
        $valid_form_inputs['password'] = $signup_password;
    }
    if ($signup_password != $signup_password_verify || empty($signup_password_verify)){
        $sign_up_errors[] = "Password mismatch";
    } elseif ($signup_password === $signup_password_verify) {
        $valid_form_inputs['password_verify'] = $signup_password_verify;
    }

    // //captcha
    // $captcha_response = $_POST["g-recaptcha-response"]; //googles JS API creates a hidden input with that name which creates a token when submit form
    // $secretKey = "6LepKU4tAAAAAG1uJs1k4SLbd_VIDQesqXzn9zey";
    // $verify = file_get_contents(
    // "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captcha_response}"
    // );
    // $captcha_response = json_decode($verify);
    // if (!$captcha_response->success) {
    // $sign_up_errors[] = "captcha failed";
      

    if (empty($sign_up_errors)){
    $signup_fullname = $signup_firstname . " " . $signup_lastname;
    $signup_password_secure = password_hash($signup_password, PASSWORD_DEFAULT);
        
    try {
        $stmt_add_user = $pdo->prepare("
        INSERT INTO users (full_name, email, phone_num, dob, username, password)
        VALUES (:fullname, :email, :phone, :dob, :signup_query_username, :signup_query_password)
    ");

    $stmt_add_user->execute([
        ':fullname' => $signup_fullname,
        ':email'    => $signup_email,
        ':phone'    => $signup_phone,
        ':dob'      => $signup_dob_mysql,   // MUST be yyyy-mm-dd
        ':signup_query_username' => $signup_username,
        ':signup_query_password' => $signup_password_secure
    ]);
    } catch (PDOException $e) {
        $error_log_file = __DIR__ . "/logs/general_errors.log"; // DIR is the current file directory
        $error_log_message = $e->getMessage() . date("[d-m-Y, H:i:s]") . "\n"; //  \n means new line for each error
        error_log($error_log_message, 3, $error_log_file); // the 3 ensures the error gets routed to a custom log file not the default (apache logs)
    }
 


    
    $_SESSION["account_created"] = "We have successfully created your account! Log In now to access your dashboard.";
    header("Location: login.php");
    exit;
    // }  else {
    //      $_SESSION["invalid_signup"] = "Please ensure you have entered all fields correctly";
    //     echo"<div class='incorrect_alert_signup'>
    //         <img src=warning.png alt='warning symbol' width=20px height=20px> &nbsp; {$_SESSION['invalid_signup']}<br>
    //     </div>
    //     ";
    }
    if (!empty($sign_up_errors)) {
        // ensuring valid stays on refresh - UNFINISHED
        $_SESSION["valid_form_inputs"] = $valid_form_inputs; 
        $_SESSION["sign_up_errors"] = $sign_up_errors; //errors aren't being saved either, so even if the valid value gets saved, the sign up containers block will still echo an error relating to the first name
        header("Location: signup.php");
        exit;
        // echo"<div class='sign_up_errors_container'>";
        // foreach($sign_up_errors as $key => $error){
        //     echo"<div class='sign_up_error'><img src='warning.png' width=20px height=20px><p>{$error}<p><br><br></div>"; //nbsp prevents line breaks in that position
        // }
        // echo"</div";
    }
}


    /*check that all inputs contain valid class & the recaptcha has been clicked*/
// need to check the firstname and lastname exists
// make sure its only letters for both
// make sure the length is between 2 and 20 for both
// THEN concantanate those values into a variable called $fullname
// then parse the value of the fullname variable into the mysqli database
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.8/build/css/intlTelInput.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="script.js" defer></script> <!--defer means run after your html -->
    <script src="https://www.google.com/recaptcha/api.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.8/build/js/intlTelInput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.8/build/js/utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!--<a href="https://www.flaticon.com/free-icons/uk-flag" title="uk flag icons">Uk flag icons created by IconsBox - Flaticon</a>-->
</head>
<body>
    <?php if (!empty($sign_up_errors)): ?>
    <div class='errors_container'>
        <?php foreach($sign_up_errors as $error): ?>
            <div class='error'>
                <img src='warning.png' width='20px' height='20px'>
                <p><?php echo htmlspecialchars($error); ?></p>
                <br><br>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
    <!--need to block certain words from being used as troll full names or usernames-->
    <!--add the little triangle caret things to the speech bubbles-->
    <!--change shape of speech bubbles to blobs? more realistic feel-->
    <!--if all inputs contain valid and recaptcha is a yes and email isn't in the database, create account-->
    <!--validate everything in php-->
    <!-- handle how the account will actually be created..e.g. take first name and last name, splice it together with space inbetween and then put it under fullname in the database.-->
   
        <header class="navigation_header nav_header_signup">
            <nav>
                <a class="rth" href="index.php">< Return to Home</a>
            </nav>
        </header>
         <main class="sign_up_container">

            <h1 class="form_heading sign_up_form_heading">👋 <span class="heading_highlight">Hello!</span> Fill in the boxes below and we'll get your account <span class="sign_up_header_highlight">made in seconds.</span></h1>
                <form action="" id="registrationForm" class="sign_up_form" method="POST"> <!--action is where you form data will be sent, leaving blank means send to current page -->
                    <div class="sign_up_form_group_firstname_lastname">
                        <div class="sign_up_form_group form_group">
                            <input type="text" name="sign_up_firstname" id="sign_up_firstname" placeholder="John" maxlength="20" 
                                value="<?php echo isset($valid_form_inputs['firstname']) ? htmlspecialchars($valid_form_inputs['firstname']) : ''; ?>"  
                                class="input_field <?php echo isset($valid_form_inputs['firstname']) ? 'valid' : ((!isset($valid_form_inputs['firstname']) && !empty($sign_up_errors))  ? 'invalid' : '') ;?>">
                            <label for="sign_up_firstname">Firstname <span class="required_asterisk">*</span></label>
                        </div>
                        <div class="sign_up_form_group form_group">
                            <input type="text" name="sign_up_lastname" id="sign_up_lastname" placeholder="Doe" maxlength="20"
                                value="<?php echo isset($valid_form_inputs['lastname']) ? htmlspecialchars($valid_form_inputs['lastname']) : ''; ?>"  
                                class="input_field <?php echo isset($valid_form_inputs['lastname']) ? 'valid' : ((!isset($valid_form_inputs['lastname']) && !empty($sign_up_errors))  ? 'invalid' : '') ;?>">
                            <label for="sign_up_lastname">Lastname <span class="required_asterisk">*</span></label>
                        </div>
                    </div>
                    <!--just do first name last name AND TABLES IS ALL WRONG SHOULD JUST BE ID ON LEFT (ROW)AND REST OF DETAILS ON TOP (COLUMN)-->
                    <div class="sign_up_form_group form_group">
                        <input type="text" name="sign_up_email" id="sign_up_email" placeholder="john.doe@[emailprovider].com" maxlength="50"
                            value="<?php echo isset($valid_form_inputs['email']) ? htmlspecialchars($valid_form_inputs['email']) : ''; ?>"  
                            class="email_input input_field <?php echo isset($valid_form_inputs['email']) ? 'valid' : ((!isset($valid_form_inputs['email']) && !empty($sign_up_errors))  ? 'invalid' : '');?>">
                        <label for="sign_up_email">Email <span class="required_asterisk">*</span></label>
                    </div>
                    <div class="sign_up_form_group_phone_dob">
                        <div class="sign_up_form_group form_group">
                            <input type="tel" name="sign_up_phone_num" id="sign_up_phone_num" maxlength="10" placeholder="9876543210"
                            value="<?php echo isset($valid_form_inputs['phone']) ? htmlspecialchars($valid_form_inputs['phone']) : ''; ?>"  
                            class="input_field <?php echo isset($valid_form_inputs['phone']) ? 'valid' : ((!isset($valid_form_inputs['phone']) && !empty($sign_up_errors))  ? 'invalid' : '') ;?>">
                        <label for="sign_up_phone_num" id="sign_up_phone_label">Phone <span class="required_asterisk">*</span></label>
                    </div>
                    <div class="sign_up_form_group form_group">
                        <input type="text" name="sign_up_dob" id="sign_up_dob" maxlength="10" placeholder="05/04/2007"
                            value="<?php echo isset($valid_form_inputs['dob']) ? htmlspecialchars($valid_form_inputs['dob']) : ''; ?>"  
                            class="input_field <?php echo isset($valid_form_inputs['dob']) ? 'valid' : ((!isset($valid_form_inputs['dob']) && !empty($sign_up_errors))  ? 'invalid' : '') ;?>">
                        <label for="sign_up_dob">Date of birth <span class="required_asterisk">*</span></label>
                    </div>
                    </div>
                    <div class="sign_up_form_group sign_up_form_user_input_reminder_username form_group">
                        <input type="text" name="sign_up_username" id="sign_up_username" placeholder="johndoe_538" maxlength="20"
                            value="<?php echo isset($valid_form_inputs['username']) ? htmlspecialchars($valid_form_inputs['username']) : ''; ?>"  
                            class="username_input input_field <?php echo isset($valid_form_inputs['username']) ? 'valid' : ((!isset($valid_form_inputs['username']) && !empty($sign_up_errors))  ? 'invalid' : ''); ?>"> 
                        <label for="sign_up_username">Username <span class="required_asterisk">*</span></label>
                    </div>
                    <div class="sign_up_form_group sign_up_form_user_input_reminder_password form_group">
                        <input type="password" name="sign_up_password" id="sign_up_password" minlength="8" maxlength="40"
                            value="<?php echo isset($valid_form_inputs['password']) ? htmlspecialchars($valid_form_inputs['password']) : ''; ?>"  
                            class="password_input input_field <?php echo isset($valid_form_inputs['password']) ? 'valid' : ((!isset($valid_form_inputs['password']) && !empty($sign_up_errors))  ? 'invalid' : '') ;?>">
                        <label for="sign_up_password">Password <span class="required_asterisk">*</span></label>
                    </div>
                    <div class="sign_up_form_group form_group">
                        <input type="password" name="sign_up_password_verify" id="sign_up_password_verify" minlength="8" maxlength="40"
                            value="<?php echo isset($valid_form_inputs['password_verify']) ? htmlspecialchars($valid_form_inputs['password_verify']) : ''; ?>"  
                            class="password_verify_input input_field <?php echo isset($valid_form_inputs['password_verify']) ? 'valid' : ((!isset($valid_form_inputs['password_verify']) && !empty($sign_up_errors))  ? 'invalid' : '') ;?>">
                        <label for="sign_up_password_verify">Verify Password <span class="required_asterisk">*</span></label>
                    </div>
                    <div class="sign_up_form_group_bottom_row">
                        <div class="sign_up-form_bottom_row_1">
                            <div class="g-recaptcha" data-sitekey="6LepKU4tAAAAAG1uJs1k4SLbd_VIDQesqXzn9zey" name="g-recaptcha-response"></div>
                        </div>
                        <!--GOTTA MAKE THE CAPTCHA LOOK CLEANER SEARCH VID-->
                        <div class="sign_up_form_bottom_row_2">
                            <input class="button" type="submit" name="sign_up_submit" value="Create Account">
                        </div>
                    </div>  
                </form>
        
    </main>
    <script>

// INPUT VALID/INVALID LOGIC
  const phoneInputField = document.querySelector("#sign_up_phone_num");
  const phoneInput = window.intlTelInput(phoneInputField, {
    // Automatically detect user's country or default to UK
    initialCountry: "gb", 
    // Shows the country dial code (+44, etc.) right next to the flag
    onlyCountries: ["gb"],
    preferredCountries: [],
    separateDialCode: true, 
    // Only load the countries you want, or leave this out to load all of them
  });



// STOP USER FROM SUBMITTING FORM IF THEY PRESS ENTER
const form = document.getElementById("registrationForm");
form.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        event.preventDefault(); // stops the form from submitting
    }
});


// VALIDATION
// FIRST NAME
const firstNameInput = document.getElementById("sign_up_firstname");
firstNameInput.addEventListener("input", () => {
    firstNameInput.value = firstNameInput.value.replace(/[^A-Za-z]/g, "");
    const firstNameValue = firstNameInput.value.trim();
    const firstNameRestrictions = /^[A-Z{1}][a-z]{1,19}$/;
    if (firstNameRestrictions.test(firstNameValue)) {
        firstNameInput.classList.add("valid");
        firstNameInput.classList.remove("invalid");
    } else {
        firstNameInput.classList.add("invalid");
        firstNameInput.classList.remove("valid");
    }
    if (firstNameInput.value.trim() === ""){
        firstNameInput.classList.remove("invalid");
    }

});
//LASTNAME
const lastNameInput = document.getElementById("sign_up_lastname");
lastNameInput.addEventListener("input", () => {
    lastNameInput.value = lastNameInput.value.replace(/[^A-Za-z]/g, "");
    const lastNameValue = lastNameInput.value.trim();
    const lastNameRestrictions = /^[A-Z{1}][a-z]{1,19}$/;
    if (lastNameRestrictions.test(lastNameValue)) {
        lastNameInput.classList.add("valid");
        lastNameInput.classList.remove("invalid");
    } else {
        lastNameInput.classList.add("invalid");
        lastNameInput.classList.remove("valid");
    }
    if (lastNameInput.value.trim() === ""){
        lastNameInput.classList.remove("invalid");
    }

});

// PHONE only numbers, and only 10 numbers
const phoneNumInput = document.getElementById("sign_up_phone_num");
phoneNumInput.addEventListener("input", () => {
    phoneNumInput.value = phoneNumInput.value.replace(/[^\d ]/g, "");
    const phoneNumValue = phoneNumInput.value.trim();
    const iti = window.intlTelInputGlobals.getInstance(phoneNumInput);
    const phoneNumInputWrapper = document.querySelector(".iti");
    const phoneCorrectFormat = /^[0-9]{10}$/
       if (phoneCorrectFormat.test(phoneNumValue)){
        phoneNumInputWrapper.classList.add("valid");
        phoneNumInput.classList.add("valid");
        phoneNumInputWrapper.classList.remove("invalid");
        phoneNumInput.classList.remove("invalid");
    } else {
        phoneNumInputWrapper.classList.add("invalid");
        phoneNumInput.classList.add("invalid");
        phoneNumInputWrapper.classList.remove("valid");
        phoneNumInput.classList.remove("valid");
    }
    if (phoneNumInput.value.trim() === ""){
        phoneNumInput.classList.remove("invalid");
        phoneNumInputWrapper.classList.remove("invalid");
    }
});
//DOB restrict users from typing...
/* open and close the calendar on/off focus */
const dobInput = document.getElementById("sign_up_dob");
const dobCorrectFormat = /^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/;

function dobValidate(){
    const dobInputValue = dobInput.value.trim();
    if (dobCorrectFormat.test(dobInputValue)){
        dobInput.classList.add("valid");
        dobInput.classList.remove("invalid");
    } else {
        dobInput.classList.remove("valid");
        dobInput.classList.add("invalid");
}}

dobInput.addEventListener("input", dobValidate);
dobInput.addEventListener("change", dobValidate);

const today = new Date(); 
const thirteenYearsAgo = new Date(
    today.getFullYear() - 13,
    today.getMonth(),
    today.getDate()
);

flatpickr("#sign_up_dob", {
    maxDate: thirteenYearsAgo,
    allowInput: false,
    dateFormat: "d/m/Y",
    onChange: dobValidate

});



//REMOVING VALID/INVALID CLASSES ON USER UNFOCUS
// document.querySelectorAll("input").forEach(input => {
//     input.addEventListener("blur", () => {
//         if (input.classList.contains("valid")){
//             input.value = input.value;
//         } else {
//             input.classList.remove("valid", "invalid");
//             input.value = "";
//         }
//     });
// }); 
// document.getElementById("registrationForm").addEventListener("submit", function(event) {
//     // 1. Get the reCAPTCHA response token
//     const captchaResponse = grecaptcha.getResponse();
//     // 2. If the response is empty, the user hasn't ticked the box
//     if (captchaResponse.length === 0) {
//         // Stop the form from submitting/refreshing the page
//         event.preventDefault(); 
//         // Alert the user (you can replace this with a beautiful error message later)
//         alert("Please verify that you are not a robot before submitting the form.");
//         return false;
//     }
//     // If the response exists, the form will submit naturally to your action URL!
//     console.log("Success! CAPTCHA completed token: ", captchaResponse);
// });

// const allInputsRemoveInvalid = document.querySelectorAll(".input_field");
// allInputsRemoveInvalid.forEach(input => {
//     input.addEventListener("input", () => {
//         if (input.value.trim() === ""){
//         input.classList.remove("invalid");
//     }
//     });
// });







</script>
</body>
</html>