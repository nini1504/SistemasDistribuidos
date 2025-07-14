<?php
include 'db.php';

$crm = isset($_GET['medico']) ? intval($_GET['medico']) : 0;

// Buscar dados do médico
$stmtMedico = $pdo->prepare("SELECT * FROM medico WHERE crm = :crm");
$stmtMedico->bindValue(':crm', $crm, PDO::PARAM_INT);
$stmtMedico->execute();
$medico = $stmtMedico->fetch();

if (!$medico) {
    echo "<h2>Médico não encontrado.</h2>";
    exit;
}

// Buscar avaliações do médico
$stmtAvaliacoes = $pdo->prepare("
    SELECT c.avaliacao, c.data_hora, p.nome AS nome_paciente
    FROM consulta c
    JOIN paciente p ON c.cpf_paciente = p.cpf
    WHERE c.crm_medico = :crm
    ORDER BY c.data_hora DESC
");
$stmtAvaliacoes->bindValue(':crm', $crm, PDO::PARAM_INT);
$stmtAvaliacoes->execute();
$avaliacoes = $stmtAvaliacoes->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dados Pessoais e Consultas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estiloNovo.css" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>

    <style>
        html,
        body {
            height: auto !important;
            overflow: auto !important;
            position: relative !important;
            overflow-x: hidden !important;
        }

        /* Se houver algum container específico com problema de scroll */
        .container,
        .main-content,
        .page-content {
            height: auto !important;
            overflow: visible !important;
            min-height: 100vh;
        }
    </style>

    <!-- Adicione este estilo específico para esta página no head -->
    <style>
        /* Estilos específicos para corrigir o problema nesta página */
        body {
            padding-top: 80px !important;
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh !important;
            display: block !important;
        }

        .container {
            margin-top: 20px;
            padding-top: 20px;
        }

        html {
            overflow-y: auto !important;
            height: auto !important;
        }

        /* Garante que o conteúdo não seja cortado */
        .card {
            margin-bottom: 30px;
        }
    </style>

    <script>
        // Garantir que o scroll esteja habilitado quando a página carregar
        document.addEventListener('DOMContentLoaded', function () {
            document.body.style.overflow = 'auto';
            document.documentElement.style.overflow = 'auto';

            // Se houver algum elemento que esteja bloqueando o scroll, encontre-o e corrija
            const fixElements = document.querySelectorAll('.fixed-element, .modal, .overlay');
            fixElements.forEach(function (element) {
                if (window.getComputedStyle(element).position === 'fixed') {
                    element.style.height = 'auto';
                    element.style.overflow = 'visible';
                }
            });
        });
    </script>
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
    <div class="container">
        <h1 class="titulo-principal">Dados do Médico</h1>
        <div class="card card-dados div-medico">
            <div>
                <?php if (!empty($medico['foto'])): ?>
                    <img class="foto-medico" src="<?= htmlspecialchars($medico['foto']) ?>"
                        alt="Foto do Dr. <?= htmlspecialchars($medico['nome']) ?>">
                <?php else: ?>
                    <div class="avatar-medico">
                        <i class="fas fa-user-md"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dados-lista">
                <div class="dado-item"><i class="fas fa-user icone"></i>
                    <div class="dado-conteudo">
                        <p class="dado-label">Nome:</p>
                        <p class="dado-valor"><?= htmlspecialchars($medico['nome']) ?></p>
                    </div>
                </div>
                <div class="dado-item"><i class="fas fa-map-marker-alt icone"></i>
                    <div class="dado-conteudo">
                        <p class="dado-label">Endereço:</p>
                        <p class="dado-valor"><?= htmlspecialchars($medico['endereco_clinica']) ?></p>
                    </div>
                </div>
                <div class="dado-item"><i class="fas fa-envelope icone"></i>
                    <div class="dado-conteudo">
                        <p class="dado-label">E-mail:</p>
                        <p class="dado-valor"><?= htmlspecialchars($medico['email']) ?></p>
                    </div>
                </div>
                <div class="dado-item"><i class="fas fa-stethoscope icone"></i>
                    <div class="dado-conteudo">
                        <p class="dado-label">Especialidade:</p>
                        <p class="dado-valor"><?= htmlspecialchars($medico['especialidade']) ?></p>
                    </div>
                </div>
            </div>
            <div class="agendamento-col">
                <i class="fas fa-calendar-check icone-agendamento"></i>
                <a href="pagAgendamento.php?medico=<?= $medico['crm'] ?>&especialidade=<?= urlencode($medico['especialidade']) ?>"
                    class="botao">
                    Agendar Consulta
                </a>
            </div>
        </div>


        <div class="card card-consultas">
            <h2 class="titulo-avaliacoes">Avaliações</h2>
            <?php if (count($avaliacoes) > 0): ?>
                <div class="consultas-lista">
                    <?php foreach ($avaliacoes as $aval): ?>
                        <div class="consulta-item">
                            <div class="consulta-info">
                                <p class="consulta-medico">
                                    <strong><?= htmlspecialchars($aval['nome_paciente'] ?? 'Paciente desconhecido') ?></strong>
                                </p>
                                <p class="texto-pequeno">
                                    <?= $aval['data_hora'] ? date('d/m/Y', strtotime($aval['data_hora'])) : 'Data indisponível' ?>
                                </p>
                                <p class="consulta-medico">
                                    <?= nl2br(htmlspecialchars($aval['avaliacao'] ?? 'Sem avaliação.')) ?>
                                </p>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="consulta-medico">Este médico ainda não recebeu avaliações.</p>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
