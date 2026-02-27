<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejercicio5</title>
</head>
<body>

<?php

// si la lista no existia que este vacia si existe pues sera lo de lista
$lista_nombres = isset($_POST['lista']) ? $_POST['lista'] : [];


// si se le ha dado un nombre  y no está vacio 
if (isset($_POST['nombre']) && $_POST['nombre'] !== '') {
    $lista_nombres[] = $_POST['nombre'];
}
?>

<form method="POST">
    <input type="text" name="nombre">

    <?php
    foreach ($lista_nombres as $n) {
        echo '<input type="hidden" name="lista[]" value="' . $n . '">';
    }
    ?>

    <button type="submit">Enviar</button>
</form>

<ul>
<?php
foreach ($lista_nombres as $n) {
    echo "<li>$n</li>";
}
?>
</ul>

</body>
</html>
