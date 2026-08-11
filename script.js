// make home page buttons work, maybe we could've just used anchor tags
document.getElementById("log_in_button_homepage")?.addEventListener("click", () => { // '?' means only apply event listener if it exists
    window.location.href = "login.php";
});
document.getElementById("sign_up_button_homepage")?.addEventListener("click", () => {
    window.location.href = "signup.php";
});
// only allow these alert to show for set times
setTimeout(() => {
    document.querySelectorAll(".success_alert, .incorrect_alert")
        .forEach(alert => alert.style.transform = 'translateY(-100%)');
}, 8000);

document.addEventListener("DOMContentLoaded", () => { // ensures the errors container is actually in the browser memory so this JS doesnt return null and break
    setTimeout(() => {
    const errors = document.querySelectorAll(".errors_container");
        errors.forEach(error => {
            error.style.transform = "translateX(100%) translateY(-50%)";
        }) //ensures it only attempts to do this when the error box is actually on the screen  
}, 15000);
});


document.addEventListener("DOMContentLoaded", () => {
    // EMAIL
    const emailInput = document.querySelectorAll(".email_input"); // can't use getelementsbyclassname
    emailInput.forEach(email_input => {
        email_input.addEventListener("input", () => {
        const emailValue = email_input.value.trim();
        const isEmailCorrectLength = emailValue.length < 50;
        const emailCorrectFormat = /^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|yahoo\.co\.uk|outlook\.com|outlook\.co\.uk)$/;

        if (isEmailCorrectLength && emailCorrectFormat.test(emailValue)){
            email_input.classList.add("valid")
            email_input.classList.remove("invalid");
        } else {
            email_input.classList.add("invalid");
            email_input.classList.remove("valid");
        } 
        if (email_input.value.trim() === ""){
            email_input.classList.remove("invalid");
        }
    });
    });

//USERNAME
/* restrictions: only letters and numbers, between 6 and 25 characters*/
/* we can just do regex for length and format validatio..*/
    const usernameInput = document.querySelectorAll(".username_input");
    usernameInput.forEach(usernameInput => {
        usernameInput.addEventListener("input", () => {
        usernameInput.value = usernameInput.value.replace(/[^A-Za-z0-9_]/g, "");
        const usernameValue = usernameInput.value.trim();
        const isUsernameCorrectLength = usernameValue.length >= 4 && usernameValue.length <= 25;
        if (isUsernameCorrectLength){
            usernameInput.classList.add("valid");
            usernameInput.classList.remove("invalid");
        } else {
            usernameInput.classList.add("invalid");
            usernameInput.classList.remove("valid");
        } 
        if (usernameInput.value.trim() === ""){
            usernameInput.classList.remove("invalid");
        }
    });
    });


//PASSWORD 
/* restrictions: between 8 and 40 characters, at least 1 capital letter, symbol and number */
    const passwordInput = document.querySelectorAll(".password_input");
    passwordInput.forEach(password_input => {
        password_input.addEventListener("input", () => {
    /* password_input.value = password_input.value.replace(/[^A-za-z0-9]/g, "");*/
        const passwordValue = password_input.value.trim();
        const passwordRestrictions = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,40}$/;
        if (passwordRestrictions.test(passwordValue)){
            password_input.classList.add("valid");
            password_input.classList.remove("invalid");
        } else {
            password_input.classList.add("invalid");
            password_input.classList.remove("valid");
        }
        if (password_input.value.trim() === ""){
            password_input.classList.remove("invalid");
        }
    });
    });

    // PASSWORD VERIFY 
    const passwordVerifyInput = document.querySelectorAll(".password_verify_input");
    passwordVerifyInput.forEach(password_verify_input => {
        password_verify_input.addEventListener("input", () => {

        const mainPasswordInput = document.querySelector(".password_input");
        if (!mainPasswordInput) return; // Exit if main password field isn't present

        const passwordValue = mainPasswordInput.value.trim();
        const passwordVerifyValue = password_verify_input.value.trim();
        if (mainPasswordInput.classList.contains("valid") && passwordValue === passwordVerifyValue){
            password_verify_input.classList.add("valid");
            password_verify_input.classList.remove("invalid");
        } else {
            password_verify_input.classList.add("invalid");
            password_verify_input.classList.remove("valid");
        }
        if (password_verify_input.value.trim() === ""){
            password_verify_input.classList.remove("invalid");
        }
        });
    });
   

});





