// ======================================================
// AFFICHER / MASQUER UN MOT DE PASSE
// ======================================================
function togglePassword(inputId, button) {

    const input = document.getElementById(inputId);

    const icon = button.querySelector("i");

    const visible = input.type === "password";

    input.type = visible ? "text" : "password";

    icon.classList.toggle("bi-eye-fill", !visible);
    icon.classList.toggle("bi-eye-slash-fill", visible);
}
