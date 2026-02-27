<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejercicio3</title>
</head>
<body>


<?php

if (isset($_POST['edad'])) {
    $edad = $_POST['edad'];

    if($edad >= 18) {
        echo "<h2>Eres mayor de edad</h2>";
    } else {
        echo "<h2>Eres menor de edad</h2>";
    }
}


?>
    
<form method="POST">

    <label>Edad</label>
    <input type="text" name="edad">
    <button type="submit">Enviar</button>

</form>

<form>


<script src="js/ej3.js"></script>
</body>
</html>