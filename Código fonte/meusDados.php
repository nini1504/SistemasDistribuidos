<?php
session_start();
include "db.php";

$isMedico = isset($_SESSION['crm']);
$isPaciente = isset($_SESSION['cpf']);

if (!$isMedico && !$isPaciente) {
    header("Location: tipoUsuario.php");
    exit();
}

try {
    if ($isPaciente) {
        $cpf = $_SESSION['cpf'];
        $stmt = $pdo->prepare("SELECT * FROM consulta WHERE cpf_paciente = ?");
        $stmt->execute([$cpf]);
        $result = $stmt;

        // Modificado para selecionar explicitamente todos os campos necessários
        $stmtPaciente = $pdo->prepare("SELECT cpf, nome, email, telefone, endereco, convenio FROM paciente WHERE cpf = ?");
        $stmtPaciente->execute([$cpf]);
        $dados = $stmtPaciente->fetch(PDO::FETCH_ASSOC);

        // Garante que todos os campos existam no array
        $dados['email'] = $dados['email'] ?? '';
        $dados['telefone'] = $dados['telefone'] ?? '';
        $dados['endereco'] = $dados['endereco'] ?? '';
        $dados['convenio'] = $dados['convenio'] ?? '';

        $titulo = "Área do Paciente";
    } elseif ($isMedico) {
        $crm = $_SESSION['crm'];
        $stmt = $pdo->prepare("SELECT * FROM consulta WHERE crm_medico = ?");
        $stmt->execute([$crm]);
        $result = $stmt;

        $stmtMedico = $pdo->prepare("SELECT crm, nome, email, telefone, endereco_clinica, especialidade FROM medico WHERE crm = ?");
        $stmtMedico->execute([$crm]);
        $dados = $stmtMedico->fetch(PDO::FETCH_ASSOC);

        // Garante que todos os campos existam no array
        $dados['email'] = $dados['email'] ?? '';
        $dados['telefone'] = $dados['telefone'] ?? '';
        $dados['endereco'] = $dados['endereco_clinica'] ?? '';

        $titulo = "Área do Médico";
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Meus Dados</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estiloNovo.css" type="text/css">
    <style>
        html,
        body {
            height: auto !important;
            overflow: auto !important;
            position: relative !important;
            overflow-x: hidden !important;
        }

        .container,
        .main-content,
        .page-content {
            height: auto !important;
            overflow: visible !important;
            min-height: 100vh;
        }

        .card-consultas .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }

        .card-consultas .badge-confirmado {
            background-color: #28a745;
        }

        .card-consultas .badge-pendente {
            background-color: #ed8936;
        }

        .dado-valor[contenteditable="true"] {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 2px 5px;
        }

        .consulta-item {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .consulta-item .mt-3 {
            flex-basis: 100%;
            margin-top: 1rem;
        }
    </style>
    <style>
        /* Estilos específicos para corrigir o problema de sobreposição nesta página */
        body.with-navbar {
            padding-top: 90px !important;
        }
        
        .titulo-principal {
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }
        
        .container {
            position: relative;
            z-index: 1;
            padding-top: 20px;
        }
        
        @media (max-width: 768px) {
            body.with-navbar {
                padding-top: 100px !important;
            }
        }
    </style>
</head>

<body class="with-navbar">
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="Home.php">
                <img src="imagens/logo.png" alt="" height="50" class="d-inline-block align-top">
            </a>
            <a href="logout.php" class="botao-logout ms-auto" title="Sair">
                <span class="material-icons" style="color: white;">logout</span>
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

    <main class="container" id="main-content">
        <h1 class="titulo-principal"><?= $titulo ?></h1>
        <div class="grid-container">
            <!-- Card Dados -->
            <div class="card card-dados">
                <div class="perfil-container">
                    <div class="avatar">
                        <span><?= strtoupper(substr($dados['nome'], 0, 2)) ?></span>
                    </div>
                    <h2 class="titulo-card">Dados Pessoais</h2>
                    <button class="botao-editar" id="botao-editar">
                        <i class="fas fa-pencil-alt"></i> Editar
                    </button>
                </div>
                <div class="dados-lista">
                    <?php if (isset($dados['cpf'])): ?>
                        <div class="dado-item"><i class="fas fa-id-card icone"></i>
                            <div class="dado-conteudo">
                                <p class="dado-label">CPF:</p>
                                <p class="dado-valor"><?= $dados['cpf'] ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="dado-item"><i class="fas fa-id-card icone"></i>
                            <div class="dado-conteudo">
                                <p class="dado-label">CRM:</p>
                                <p class="dado-valor"><?= $dados['crm'] ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="dado-item"><i class="fas fa-user icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">Nome:</p>
                            <p class="dado-valor"><?= $dados['nome'] ?></p>
                        </div>
                    </div>
                    <?php if (isset($dados['especialidade'])): ?>
                        <div class="dado-item"><i class="fas fa-stethoscope icone"></i>
                            <div class="dado-conteudo">
                                <p class="dado-label">Especialidade:</p>
                                <p class="dado-valor"><?= $dados['especialidade'] ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="dado-item dado-editavel"><i class="fas fa-envelope icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">E-mail:</p>
                            <p class="dado-valor dado-editavel-conteudo" data-campo="email">
                                <?= !empty($dados['email']) ? $dados['email'] : 'Não informado' ?>
                            </p>
                        </div>
                    </div>
                    <div class="dado-item dado-editavel"><i class="fas fa-phone icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">Telefone:</p>
                            <p class="dado-valor dado-editavel-conteudo" data-campo="telefone">
                                <?= !empty($dados['telefone']) ? $dados['telefone'] : 'Não informado' ?>
                            </p>
                        </div>
                    </div>
                    <div class="dado-item dado-editavel"><i class="fas fa-map-marker-alt icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">Endereço:</p>
                            <p class="dado-valor dado-editavel-conteudo" data-campo="endereco">
                                <?= !empty($dados['endereco']) ? $dados['endereco'] : 'Não informado' ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($isPaciente): ?>
                        <div class="dado-item dado-editavel"><i class="fas fa-heartbeat icone"></i>
                            <div class="dado-conteudo">
                                <p class="dado-label">Convênio:</p>
                                <p class="dado-valor dado-editavel-conteudo" data-campo="convenio">
                                    <?= !empty($dados['convenio']) ? $dados['convenio'] : 'Não informado' ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card Consultas -->
            <div class="card card-consultas">
                <h2 class="titulo-card">Consultas</h2>
                <div class="consultas-lista">
                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)):
                        if ($isPaciente) {
                            $stmtMed = $pdo->prepare("SELECT nome, especialidade FROM medico WHERE crm = ?");
                            $stmtMed->execute([$row['crm_medico']]);
                            $info = $stmtMed->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $stmtPac = $pdo->prepare("SELECT nome FROM paciente WHERE cpf = ?");
                            $stmtPac->execute([$row['cpf_paciente']]);
                            $info = $stmtPac->fetch(PDO::FETCH_ASSOC);
                        }
                        // Formata a data e hora separadamente
                        $dataHoraAtual = new DateTime(); // agora
                        $dataHora = DateTime::createFromFormat('Y-m-d H:i:s', $row['data_hora']);
                        $dataFormatada = $dataHora ? $dataHora->format('d/m/Y') : '';
                        $horaFormatada = $dataHora ? $dataHora->format('H:i') : '';
                        $consultaPassada = $dataHora < $dataHoraAtual;

                        // Verifica se a consulta já passou
                        $consultaPassada = $dataHora && $dataHora < new DateTime();
                        ?>
                        <div class="consulta-item">
                            <div class="consulta-info">
                                <h3 class="consulta-especialidade"><?= $info['especialidade'] ?? '' ?></h3>
                                <?php if ($isMedico): ?>
                                    <p class="consulta-medico"><strong>Paciente:</strong> <?= $info['nome'] ?></p>
                                <?php else: ?>
                                    <p class="consulta-medico"><?= $info['nome'] ?></p>
                                <?php endif; ?>
                                <div class="consulta-detalhes">
                                    <div class="consulta-data">
                                        <i class="fas fa-calendar-alt icone-pequeno"></i>
                                        <span class="dado-valor"><?= $dataFormatada ?></span>
                                    </div>
                                    <div class="consulta-hora">
                                        <i class="fas fa-clock icone-pequeno"></i>
                                        <span class="dado-valor"><?= $horaFormatada ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="consulta-acoes">
                                <?php
                                $status = isset($row['status']) ? $row['status'] : 'Pendente';
                                $badgeClass = $status === 'Confirmado' ? 'badge-confirmado' : 'badge-pendente';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $status ?></span>

                                <?php if ($isMedico && $status === 'Pendente'): ?>
                                    <a href="confirmarConsulta.php?id=<?= $row['id_consulta'] ?>"
                                        class="botao botao-confirmar">Confirmar</a>
                                <?php endif; ?>

                                <?php if ($isPaciente): ?>
                                    <a href="deletar_consulta.php?id_consulta=<?= $row['id_consulta'] ?>"
                                        class="botao botao-deletar"
                                        onclick="return confirm('Tem certeza que deseja cancelar esta consulta?');">
                                        <i class="fas fa-trash-alt"></i> Cancelar
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Parte de avaliação movida para baixo dos dados da consulta -->
                            <div class="mt-3 w-100">
                                <?php if ($isPaciente && $consultaPassada && empty($row['avaliacao'])): ?>
                                    <form action="avaliarConsulta.php" method="POST" class="form-avaliacao">
                                        <input type="hidden" name="id_consulta" value="<?= $row['id_consulta'] ?>">
                                        <div class="mb-2">
                                            <label for="avaliacao_<?= $row['id_consulta'] ?>">Avaliação:</label>
                                            <textarea name="avaliacao" id="avaliacao_<?= $row['id_consulta'] ?>"
                                                class="form-control" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">Enviar Avaliação</button>
                                    </form>
                                <?php elseif (!empty($row['avaliacao'])): ?>
                                    <p><strong>Sua Avaliação:</strong> <?= htmlspecialchars($row['avaliacao']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>

                </div>

                <?php if ($isPaciente): ?>
                    <a class="botao botao-full" href="pagAgendamento.php">Agendar Nova Consulta</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('botao-editar').addEventListener('click', function () {
            const campos = document.querySelectorAll('.dado-editavel-conteudo');
            const editando = this.classList.toggle('editando-ativo');

            if (editando) {
                this.innerHTML = '<i class="fas fa-save"></i> Salvar';
                campos.forEach(campo => {
                    campo.setAttribute('contenteditable', 'true');
                    if (campo.textContent === 'Não informado') {
                        campo.textContent = '';
                    }
                });
            } else {
                this.innerHTML = '<i class="fas fa-pencil-alt"></i> Editar';
                campos.forEach(campo => campo.setAttribute('contenteditable', 'false'));

                const formData = new FormData();
                campos.forEach(campo => {
                    const campoName = campo.getAttribute('data-campo');
                    const valor = campo.textContent.trim() === '' ? '' : campo.textContent.trim();
                    formData.append(campoName, valor);
                });

                // Adiciona o tipo de usuário ao FormData
                formData.append('tipo', '<?= $isPaciente ? "paciente" : "medico" ?>');

                fetch('atualizarDados.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Erro na resposta do servidor: ' + res.status);
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.status === 'sucesso') {
                            alert(data.mensagem);
                            location.reload();
                        } else {
                            throw new Error(data.mensagem || 'Erro ao atualizar dados');
                        }
                    })
                    .catch(err => {
                        alert('Erro ao salvar os dados: ' + err.message);
                        console.error('Erro:', err);
                    });
            }
        });
    </script>
    <script>
        // Script para garantir que o conteúdo não seja sobreposto pelo cabeçalho
        document.addEventListener('DOMContentLoaded', function() {
            // Ajusta o padding-top do corpo da página
            document.body.style.paddingTop = '90px';
            
            // Garante que o título principal esteja visível
            const titulo = document.querySelector('.titulo-principal');
            if (titulo) {
                titulo.style.marginTop = '20px';
            }
            
            // Ajusta a posição do conteúdo principal
            const mainContent = document.getElementById('main-content');
            if (mainContent) {
                mainContent.style.position = 'relative';
                mainContent.style.zIndex = '1';
            }
        });
    </script>
</body>

</html>
