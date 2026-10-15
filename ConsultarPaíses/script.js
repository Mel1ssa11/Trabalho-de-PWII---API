const formulario = document.getElementById("formulario");
const campo = document.getElementById("pais");
const botao = formulario.querySelector("button");
const resultado = document.getElementById("resultado");

const textoBotao = botao.textContent;

formulario.addEventListener("submit", function (evento) {

    const pais = campo.value.trim();

    if (pais === "") {
        evento.preventDefault();

        resultado.innerHTML = '<p class="aviso erro">Digite o nome de um país antes de consultar.</p>';
        campo.focus();
        return;
    }

    botao.textContent = "Consultando...";
    botao.disabled = true;
});


window.addEventListener("pageshow", function () {
    botao.textContent = textoBotao;
    botao.disabled = false;
});