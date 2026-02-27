let formulario = document.querySelector('form')

formulario.addEventListener('submit', function(event) {
    let inputNombre = document.querySelector('input[name="edad"]')
    let valorNombre = inputNombre.value;


    if(valorNombre == "") {
        event.preventDefault();
        alert("Escribe algo")
    }
})