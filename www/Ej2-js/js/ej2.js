const formulario = document.querySelector('form');

formulario.addEventListener('submit', function(event) {
    
    event.preventDefault();

    const nombre = document.querySelector('input[name="nombre"]').value;

    alert("Hola " + nombre);
    
    console.log("Formulario procesado para: " + nombre);
});