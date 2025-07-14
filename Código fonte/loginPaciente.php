<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cpf = $_POST['cpf'];
    $senha = $_POST['password'];

    try {
        // Busca paciente pelo CPF
        $stmt = $pdo->prepare("SELECT * FROM paciente WHERE cpf = ?");
        $stmt->execute([$cpf]);

        if ($stmt->rowCount() == 1) {
            $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica a senha
            if (password_verify($senha, $paciente['senha'])) {
                $_SESSION['cpf'] = $paciente['cpf'];
                $_SESSION['nome'] = $paciente['nome'];

                header("Location: Home.php");
                exit();
            } else {
                $error = "Senha incorreta.";
            }
        } else {
            $error = "CPF não encontrado.";
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
    <title>Login Paciente</title>
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
                        <a class="nav-link" href="meusDados.php">Meus Dados</a>
                    </li>
                    <li class="nav-item">
                        <select class="login-select form-select" onchange="window.location.href=this.value">
                            <option class="item-login" value="">Login</option>
                            <option class="item-login" value="loginMedico.php">Médico</option>
                            <option class="item-login" value="loginPaciente.php">Paciente</option>
                        </select>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="login-container">
        <h1> Faça seu login </h1>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="loginPaciente.php">
            <input type="text" id="cpf" name="cpf" placeholder="Digite seu CPF" required>
            <br><br>
            <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
            <br><br>
            <button type="submit" class="botao">Entrar</button>
            <br>
            <a href="CadastroPaciente.php" class="botao">Criar cadastro</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
