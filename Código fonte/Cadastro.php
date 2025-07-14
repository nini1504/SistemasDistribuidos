<?php
session_start();
include 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cpf = $_POST['cpf'];
    $senha = password_hash($cpf, PASSWORD_DEFAULT); // CPF como senha padrão
    $nome = $_POST['nome'];
    $endereco = $_POST['endereco'];
    $telefone = $_POST['telefone'];
    $convenio = $_POST['convenio'];
    $email = $_POST['email'];

    try {
        // Verificar se já existe um paciente com o mesmo CPF
        $check_stmt = $pdo->prepare("SELECT * FROM paciente WHERE cpf = ?");
        $check_stmt->execute([$cpf]);

        if ($check_stmt->rowCount() > 0) {
            $error = "Já existe um paciente com este CPF.";
        } else {
            // Inserir o novo paciente COM SENHA
            $insert_stmt = $pdo->prepare("INSERT INTO paciente (cpf, nome, telefone, convenio, endereco, email, senha) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($insert_stmt->execute([$cpf, $nome, $telefone, $convenio, $endereco, $email, $senha])) {
                $success = "Cadastro realizado com sucesso!";
                header("refresh:2; url=loginPaciente.php");
                exit();
            } else {
                $error = "Erro ao cadastrar o paciente.";
            }
        }
    } catch (PDOException $e) {
        $error = "Erro no banco de dados: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Cadastro</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estiloNovo.css" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="Home.php">
                <img src="imagens/logo.png" alt="" height="50" class="d-inline-block align-top">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="pagAgendamento.php">Agendar</a>
                    </li>
                    <li class="nav-item">
                        <select class="form-select" onchange="window.location.href=this.value">
                            <option value="">Login</option>
                            <option value="loginMedico.php">Médico</option>
                            <option value="loginPaciente.php">Paciente</option>
                        </select>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Usuario.php">Meus Dados</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="cad-container">
        <h1>Realize seu cadastro</h1>

        <!-- mensagens -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="Cadastro.php">
            <input type="text" id="cpf" name="cpf" placeholder="Digite seu CPF (11 dígitos)" pattern="[0-9]{11}" maxlength="11" required><br>
            <input type="text" id="nome" name="nome" placeholder="Digite seu Nome Completo" required><br>
            <input type="text" id="endereco" name="endereco" placeholder="Digite seu Endereço" required><br>
            <input type="text" id="telefone" name="telefone" placeholder="Digite seu Telefone (00) 00000-0000" maxlength="15" required><br>

            <select id="convenio" name="convenio" class="form-select" required>
                <option value="" disabled selected>Selecione seu Convênio</option>
                <option value="Unimed">Unimed</option>
                <option value="SulAmérica">SulAmérica</option>
                <option value="Bradesco Saúde">Bradesco Saúde</option>
                <option value="Amil">Amil</option>
                <option value="Outro">Outro</option>
                <option value="Nenhum">Não possuo convênio</option>
            </select><br>

            <input type="email" id="email" name="email" placeholder="Digite seu E-mail" required><br>
            <button type="submit" class="botao">Cadastrar</button>
        </form>
    </div>
</body>
</html>
