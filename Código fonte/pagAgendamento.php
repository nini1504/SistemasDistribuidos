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

// Verificar se veio da página do médico
$crm_medico = isset($_GET['medico']) ? intval($_GET['medico']) : 0;
$especialidade_selecionada = isset($_GET['especialidade']) ? $_GET['especialidade'] : '';

// Processar agendamento completo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data_hora']) && isset($_POST['medico'])) {
    $nome_medico = $conexao->real_escape_string($_POST['medico']);
    $data_hora = $conexao->real_escape_string($_POST['data_hora']);

    // Verifica se o paciente está logado
    if (!isset($_SESSION['cpf'])) {
        $erro = "Você precisa estar logado como paciente para agendar uma consulta.";
    } else {
        $cpf_paciente = $conexao->real_escape_string($_SESSION['cpf']);

        $resultado = $conexao->query("SELECT crm FROM medico WHERE nome='$nome_medico'");
        if ($resultado && $resultado->num_rows > 0) {
            $linha = $resultado->fetch_assoc();
            $crm_medico = $linha['crm'];

            $sql = "INSERT INTO consulta(crm_medico, cpf_paciente, data_hora) 
                    VALUES ('$crm_medico', '$cpf_paciente', '$data_hora')";

            if ($conexao->query($sql) === TRUE) {
                header("Location: meusDados.php");
                exit;
            } else {
                $erro = "Erro ao cadastrar consulta: " . $conexao->error;
            }
        }
    }
}
// Buscar médico específico se veio da página do médico
$medico_selecionado = null;
if ($crm_medico > 0) {
    $result = $conexao->query("SELECT nome, especialidade FROM medico WHERE crm = $crm_medico");
    if ($result && $result->num_rows > 0) {
        $medico_selecionado = $result->fetch_assoc();
        $especialidade_selecionada = $medico_selecionado['especialidade'];
    }
}

// Buscar todas especialidades
$especialidades = $conexao->query("SELECT especialidade FROM medico GROUP BY especialidade");
if (!$especialidades) {
    die("Erro na consulta: " . $conexao->error);
}

// Buscar médicos conforme especialidade selecionada
$medicos = array();
$especialidade_para_busca = '';

if ($crm_medico > 0) {
    // Se veio da página do médico, usa a especialidade dele
    $especialidade_para_busca = $especialidade_selecionada;
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['especialidade'])) {
    // Se foi submetido o formulário de especialidade
    $especialidade_para_busca = $conexao->real_escape_string($_POST['especialidade']);
}

if (!empty($especialidade_para_busca)) {
    $result_medicos = $conexao->query("SELECT nome FROM medico WHERE especialidade = '$especialidade_para_busca'");
    if ($result_medicos) {
        while ($row = $result_medicos->fetch_assoc()) {
            $medicos[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos - Agende Já</title>
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

    <!-- Form Section -->
    <section class="form-section">
        <div class="login-container">
            <h1>Agendamento</h1>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>

            <?php if (!empty($medico_selecionado)): ?>
                <div class="alert alert-info mb-3">
                    Você está agendando com o <?= htmlspecialchars($medico_selecionado['nome']) ?>.
                    <a href="pagAgendamento.php" class="alert-link">Quer mudar de médico?</a>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <!-- Especialidade -->
                <div class="mb-3">
                    <select class="form-select" id="especialidade" name="especialidade" required 
                        onchange="this.form.submit()" <?= !empty($medico_selecionado) ? 'disabled' : '' ?>>
                        <option value="" disabled <?= empty($especialidade_para_busca) ? 'selected' : '' ?>>Selecione uma especialidade</option>
                        <?php 
                        $especialidades->data_seek(0);
                        while ($row = $especialidades->fetch_assoc()): ?>
                            <option value="<?= $row['especialidade'] ?>" 
                                <?= ($especialidade_para_busca == $row['especialidade']) ? 'selected' : '' ?>>
                                <?= $row['especialidade'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Médico -->
                <div class="mb-3">
                    <select class="form-select" id="medico" name="medico" required>
                        <?php if (empty($medicos)): ?>
                            <option value="" disabled selected>Selecione um médico</option>
                        <?php else: ?>
                            <?php if (!empty($medico_selecionado)): ?>
                                <option value="<?= $medico_selecionado['nome'] ?>" selected>
                                    <?= $medico_selecionado['nome'] ?>
                                </option>
                            <?php else: ?>
                                <option value="" disabled selected>Selecione um médico</option>
                                <?php foreach ($medicos as $medico): ?>
                                    <option value="<?= $medico['nome'] ?>"><?= $medico['nome'] ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Data e Hora -->
                <div class="mb-3">
                    <input type="datetime-local" class="form-control" id="data_hora" name="data_hora" required>
                </div>

                <!-- Botão de Agendar -->
                <button type="submit" class="botao">Agendar Consulta</button>
            </form>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const especialidadeSelect = document.getElementById('especialidade');
        
        // Estilo para select desabilitado quando vem do médico
        if (especialidadeSelect.disabled) {
            especialidadeSelect.style.backgroundColor = '#f8f9fa';
            especialidadeSelect.style.opacity = '1';
        }
    });
    </script>
</body>
</html>