<?php
include 'gestorBuscador.php';

$campos = [
    "Nombre"   => "usuarios.nombre",
    "Apellido" => "usuarios.apellido",
    "Email"    => "detalles.email",
    "Teléfono" => "detalles.telefono"
];

$joins = [
    [
        "tipo"  => "LEFT",
        "tabla" => "detalles",
        "on"    => "usuarios.id = detalles.usuario_id"
    ]
];

$gestor = new GestorBuscador(
    "db",
    "root",
    "root",
    "db",
    "Gestor de usuarios",
    $campos,
    "usuarios",
    $joins
);

$gestor->tabla();
?>