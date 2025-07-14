<?php
session_start();
include "db.php";

if (!isset($_SESSION['crm'])) {
    // Apenas médicos podem confirmar
    header("Location: tipoUsuario.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Atualiza o status da consulta para 'Confirmado'
    $sql = "UPDATE consulta SET status = 'Confirmado' WHERE id_consulta = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

// Redireciona de volta à página de dados
header("Location: meusDados.php"); // ou o nome correto da sua página
exit();
?>
