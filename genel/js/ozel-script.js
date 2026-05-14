

function sifreGoster(inputID, icon) {
    const sifreInput = document.getElementById(inputID);
    
    if (sifreInput.type === "password") {
        sifreInput.type = "text";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    } else {
        sifreInput.type = "password";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    }
}