<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $crm = $_POST['crm']; // Corrigido para pegar o campo correto
    $senha = $_POST['password'];

    try {
        // Buscar médico no banco de dados
        $stmt = $pdo->prepare("SELECT * FROM medico WHERE crm = ?");
        $stmt->execute([$crm]);
        $medico = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($medico) {
            // Verificar a senha com password_verify()
            if (password_verify($senha, $medico['senha'])) {

                //Login bem sucedido
                $_SESSION['crm'] = $medico['crm'];
                $_SESSION['nome'] = $medico['nome'];

                // Redirecionar para área restrita
                header("Location: home.php");
                exit();
            } else {
                $error = "CRM ou senha incorretos!";
            }
        } else {
            $error = "CRM não encontrado!";
        }
    } catch (PDOException $e) {
        $error = "Erro no sistema: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Login Médico</title>
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                        <select class="login-select form-select" onchange="window.location.href=this.value" Login>
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
        <h1>Faça seu login</h1>
        
        <!-- Exibir mensagens de erro -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Formulário corrigido -->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="text" id="crm" name="crm" placeholder="Digite seu CRM" required>
            <br><br>
            <input type="password" id="password" name="password" placeholder="Digite sua Senha" required>
            <br><br>
            <button type="submit" class="botao">Entrar</button> 
            <br>
            <a href="cadastroMedico.php" class="botao">Primeiro acesso</a>
        </form>
    </div> 
</body>
</html>