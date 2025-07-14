<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['cpf'])) {
    $idConsulta = $_POST['id_consulta'] ?? null;
    $avaliacao = trim($_POST['avaliacao'] ?? '');

    if ($idConsulta && $avaliacao !== '') {
        $stmt = $pdo->prepare("UPDATE consulta SET avaliacao = ? WHERE id_consulta = ? AND cpf_paciente = ?");
        $stmt->execute([$avaliacao, $idConsulta, $_SESSION['cpf']]);
    }
}

header("Location: meusDados.php");
exit();
