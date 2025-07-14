<?php
session_start();
include "db.php";

$response = ['status' => 'erro', 'mensagem' => 'Algo deu errado'];

try {
    // Verifica se todos os campos obrigatórios estão presentes
    if (!isset($_POST['email']) || !isset($_POST['telefone']) || !isset($_POST['tipo'])) {
        throw new Exception('Dados incompletos para atualização');
    }

    if ($_POST['tipo'] === 'paciente' && isset($_SESSION['cpf'])) {
        $cpf = $_SESSION['cpf'];
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
        $endereco = isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : '';
        $convenio = isset($_POST['convenio']) ? htmlspecialchars($_POST['convenio']) : '';

        $sql = "UPDATE paciente SET 
                email = :email, 
                telefone = :telefone, 
                endereco = :endereco, 
                convenio = :convenio 
                WHERE cpf = :cpf";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':telefone' => $telefone,
            ':endereco' => $endereco,
            ':convenio' => $convenio,
            ':cpf' => $cpf
        ]);

        if ($stmt->rowCount() > 0) {
            $response = ['status' => 'sucesso', 'mensagem' => 'Dados atualizados com sucesso'];
        } else {
            $response = ['status' => 'aviso', 'mensagem' => 'Nenhum dado foi alterado'];
        }

    } elseif ($_POST['tipo'] === 'medico' && isset($_SESSION['crm'])) {
        $crm = $_SESSION['crm'];
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
        $endereco = isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : '';

        $sql = "UPDATE medico SET 
                email = :email, 
                telefone = :telefone, 
                endereco_clinica = :endereco 
                WHERE crm = :crm";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':telefone' => $telefone,
            ':endereco' => $endereco,
            ':crm' => $crm
        ]);

        if ($stmt->rowCount() > 0) {
            $response = ['status' => 'sucesso', 'mensagem' => 'Dados atualizados com sucesso'];
        } else {
            $response = ['status' => 'aviso', 'mensagem' => 'Nenhum dado foi alterado'];
        }
    } else {
        throw new Exception('Tipo de usuário inválido ou sessão não autenticada');
    }
} catch (Exception $e) {
    $response = ['status' => 'erro', 'mensagem' => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);
?>