const form = document.querySelector(".auth-form");
const emailInput = form.querySelector('input[name="email"]');
const passwordInput = form.querySelector('input[name="password"]');
const confirmedPasswordInput = form.querySelector('input[name="password2"]');

function isEmail(email) {
    return /\S+@\S+\.\S+/.test(email);
}

function arePasswordsSame(password, confirmedPassword) {
    return password === confirmedPassword;
}

function isPasswordComplex(password) {
    return password.length >= 8
        && password.length <= 72
        && /[a-z]/.test(password)
        && /[A-Z]/.test(password)
        && /\d/.test(password);
}

function markValidation(element, condition) {
    !condition ? element.classList.add('no-valid') : element.classList.remove('no-valid');
}

function validateEmail() {
    markValidation(emailInput, isEmail(emailInput.value));
}

function validatePassword() {
    markValidation(passwordInput, isPasswordComplex(passwordInput.value));

    const condition = isPasswordComplex(passwordInput.value) && arePasswordsSame(
        passwordInput.value,
        confirmedPasswordInput.value
    );
    markValidation(confirmedPasswordInput, condition);
}

function validateForm(event) {
    validateEmail();
    validatePassword();

    if (!isEmail(emailInput.value) || !isPasswordComplex(passwordInput.value) || !arePasswordsSame(passwordInput.value, confirmedPasswordInput.value)) {
        event.preventDefault();
    }
}

emailInput.addEventListener('keyup', validateEmail);
passwordInput.addEventListener('keyup', validatePassword);
confirmedPasswordInput.addEventListener('keyup', validatePassword);
form.addEventListener('submit', validateForm);
