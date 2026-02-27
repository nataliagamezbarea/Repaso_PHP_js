


function obtenerDatos() {
fetch('../datos.php') 
.then(response => response.json())
.then (data => {
    alert ("Hola" + " " + data)
    
})
}