<?php
include 'gestorBuscador.php';


$campos = [
    "ID"       => "usuarios.id",
    "Nombre"   => "usuarios.nombre",
    "Apellido" => "usuarios.apellido",
];


$edicionSoloLectura = ["usuarios.id"];
$busquedaExcluido = ["usuarios.id"];

$creacionExcluido = ["usuarios.id"];




$gestor = new GestorBuscador(
    "db",
    "root",
    "root",
    "db",
    "Gestor de usuarios",
    $campos,
    "usuarios",
);
if (isset($busquedaExcluido)) $gestor->busquedaExcluido = $busquedaExcluido;

$gestor->tabla();
?>