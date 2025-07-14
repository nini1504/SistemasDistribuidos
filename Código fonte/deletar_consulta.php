<?php
session_start();
    $servername = "localhost";
    $usuario = "root";
    $senha = "";
    $banco = "agendeja";

    $conexao = new mysqli($servername, $usuario, $senha,$banco);
    if ($conexao->connect_error) {
        die("Erro de conexao! ". $conexao->connecr_error);
    }
    if (!isset($_GET['id_consulta']) || empty($_GET['id_consulta'])) {
        $_SESSION['mensagem'] = "ID da consulta não fornecido.";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: meusDados.php");
        exit();
    }
    $id_consulta = $_GET['id_consulta'];
    $sql = "DELETE FROM consulta WHERE id_consulta=$id_consulta";
    if ($conexao->query($sql) === TRUE) {
        $_SESSION['mensagem'] = "Consulta cancelada com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao cancelar consulta: " . $conexao->error;
        $_SESSION['tipo_mensagem'] = "erro";
    }
    header("Location: meusDados.php");
    exit();
?>