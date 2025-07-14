<?php
session_start();
include 'db.php'; 

$error = '';
$success = '';

// Processar o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $crm = $_POST['crm'];
    $senha = $_POST['password'];
    $confirmar_senha = $_POST['confirm_password'];

    if ($senha !== $confirmar_senha) {
        $error = "As senhas não coincidem!";
    } else {
        try {
            // Verificar se o CRM existe
            $stmt = $pdo->prepare("SELECT * FROM medico WHERE crm = ?");
            $stmt->execute([$crm]);
            
            if ($stmt->rowCount() > 0) {
                // Criptografar a senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Atualizar a senha no banco de dados
                $update_stmt = $pdo->prepare("UPDATE medico SET senha = ? WHERE crm = ?");
                
                if ($update_stmt->execute([$senha_hash, $crm])) {
                    $success = "Senha cadastrada com sucesso!";
                    // Redirecionar após 2 segundos
                    header("refresh:2; url=loginMedico.php");
                    exit();
                } else {
                    $error = "Erro ao atualizar a senha.";
                }
            } else {
                $error = "CRM não encontrado!";
            }
        } catch (PDOException $e) {
            $error = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Cadastro de Senha</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estiloNovo.css" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="estiloNovo.css" type="text/css">

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
    
    <div class="cad-container">
        <h1>Defina sua senha</h1>
        
        <!-- Mensagens de erro/sucesso -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="text" id="crm" name="crm" placeholder="Digite seu CRM" required>
            <br>
            <input type="password" id="password" name="password" placeholder="Crie sua Senha">
            <br>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirme sua Senha">
            <button type="submit" class="botao">Cadastrar</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação em tempo real das senhas
        document.querySelector('form').addEventListener('submit', function(e) {
            const senha = document.getElementById('password').value;
            const confirmarSenha = document.getElementById('confirm_password').value;
            
            if (senha !== confirmarSenha) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                return false;
            }          
            return true;
        });
    </script>
</body>
</html>