<?php
session_start();
include 'db.php'; // Inclui a conexão com o banco de dados

// Variáveis para controlar o tipo de usuário e seus identificadores
$is_paciente = false;
$is_medico = false;
$id_usuario_logado = null;
$tipo_usuario_logado = null; // 'paciente' ou 'medico'

// 1. VERIFICAÇÃO DE SESSÃO E REDIRECIONAMENTO
// Tenta identificar se é paciente
if (isset($_SESSION['cpf'])) {
    $is_paciente = true;
    $id_usuario_logado = $_SESSION['cpf'];
    $tipo_usuario_logado = 'paciente';
}
// Tenta identificar se é médico
elseif (isset($_SESSION['crm'])) {
    $is_medico = true;
    $id_usuario_logado = $_SESSION['crm'];
    $tipo_usuario_logado = 'medico';
}
// Se não há CPF nem CRM na sessão, redireciona para a página de login principal ou uma escolha
else {
    header("Location: Home.php"); // Ou outra página inicial/login
    exit();
}

// Variáveis para armazenar as conversas e a conversa atual
$conversas = [];
$conversa_atual = null;
$mensagens_conversa = [];
$perfil_conversa = null; // Armazenará os dados do médico ou paciente da conversa ativa

// 2. BUSCA DE CONVERSAS E DADOS DA CONVERSA ATIVA COM BASE NO TIPO DE USUÁRIO
if ($is_paciente) {
    $titulo_sidebar = "Médicos";
    $icone_sidebar = "fas fa-user-md";
    $texto_empty_state_sidebar = "Nenhuma conversa encontrada";
    $texto_empty_state_chat = "Escolha um médico na lista ao lado para começar a conversar";
    $label_chat_header = "Dr.";
    $campo_nome_conversa = 'nome_medico';
    $campo_info_conversa = 'especialidade'; // No caso do paciente, mostra a especialidade do médico
    $avatar_class_sidebar = 'avatar-medico';
    $avatar_class_chat = 'avatar-medico';
    $icone_chat_info = 'fas fa-stethoscope';

    // --- NOVA LÓGICA PARA TRATAR O PARAMETRO 'medico' (CRM) ---
    if (isset($_GET['medico']) && !isset($_GET['conversa'])) {
        $crm_medico_selecionado = $_GET['medico'];

        // Tenta encontrar uma conversa existente entre este paciente e este médico
        $stmt_check_conversa = $pdo->prepare("SELECT id_conversa FROM conversas WHERE id_paciente = ? AND id_medico = ?");
        $stmt_check_conversa->execute([$id_usuario_logado, $crm_medico_selecionado]);
        $existing_conversa = $stmt_check_conversa->fetch(PDO::FETCH_ASSOC);

        if ($existing_conversa) {
            $id_conversa_a_carregar = $existing_conversa['id_conversa'];
        } else {
            // Se não encontrou, cria uma nova conversa
            $pdo->beginTransaction(); // Inicia uma transação para garantir atomicidade
            try {
                $stmt_insert_conversa = $pdo->prepare("INSERT INTO conversas (id_paciente, id_medico) VALUES (?, ?)");
                $stmt_insert_conversa->execute([$id_usuario_logado, $crm_medico_selecionado]);
                $id_conversa_a_carregar = $pdo->lastInsertId(); // Obtém o ID da nova conversa
                $pdo->commit(); // Confirma a transação
                error_log("Nova conversa criada: " . $id_conversa_a_carregar); // Para depuração
            } catch (PDOException $e) {
                $pdo->rollBack(); // Desfaz a transação em caso de erro
                error_log("Erro ao criar nova conversa: " . $e->getMessage());
                // Poderia redirecionar para uma página de erro ou exibir uma mensagem
                header("Location: Home.php?error=chat_creation_failed");
                exit();
            }
        }
        // Redireciona para a própria página com o ID da conversa (existente ou recém-criada)
        // Isso é importante para que a URL reflita a conversa ativa e o JS possa pegar o ID_conversa
        header("Location: chat_list.php?conversa=" . $id_conversa_a_carregar);
        exit();
    }
    // --- FIM DA NOVA LÓGICA ---

    // Buscar conversas do paciente com informações dos médicos
    $stmt = $pdo->prepare("
        SELECT c.id_conversa, m.crm, m.nome AS nome_medico, m.especialidade, 
               (SELECT COUNT(*) FROM mensagens WHERE id_conversa = c.id_conversa AND remetente = 'medico' AND lida = FALSE) AS mensagens_nao_lidas,
               (SELECT mensagem FROM mensagens WHERE id_conversa = c.id_conversa ORDER BY data_envio DESC LIMIT 1) AS ultima_mensagem,
               (SELECT data_envio FROM mensagens WHERE id_conversa = c.id_conversa ORDER BY data_envio DESC LIMIT 1) AS data_ultima_mensagem
        FROM conversas c
        JOIN medico m ON c.id_medico = m.crm
        WHERE c.id_paciente = ?
        ORDER BY data_ultima_mensagem DESC
    ");
    $stmt->execute([$id_usuario_logado]); // Usa o CPF do paciente logado
    $conversas = $stmt->fetchAll();

    if (isset($_GET['conversa'])) {
        $id_conversa = $_GET['conversa'];
        $stmt = $pdo->prepare("SELECT * FROM conversas WHERE id_conversa = ? AND id_paciente = ?");
        $stmt->execute([$id_conversa, $id_usuario_logado]);
        $conversa_atual = $stmt->fetch();
        
        if ($conversa_atual) {
            $stmt = $pdo->prepare("SELECT * FROM medico WHERE crm = ?");
            $stmt->execute([$conversa_atual['id_medico']]);
            $perfil_conversa = $stmt->fetch(); // Detalhes do médico para o chat header

            $stmt = $pdo->prepare("SELECT * FROM mensagens WHERE id_conversa = ? ORDER BY data_envio ASC");
            $stmt->execute([$id_conversa]);
            $mensagens_conversa = $stmt->fetchAll();
            
            // Marcar mensagens do médico como lidas
            $stmt = $pdo->prepare("UPDATE mensagens SET lida = TRUE WHERE id_conversa = ? AND remetente = 'medico'");
            $stmt->execute([$id_conversa]);
        } else {
             // Se a conversa não pertence ao paciente logado ou não existe
            header("Location: chat_list.php?error=invalid_conversa");
            exit();
        }
    }

} elseif ($is_medico) {
    // ... (Seu código para médicos permanece inalterado por enquanto) ...
    $titulo_sidebar = "Pacientes";
    $icone_sidebar = "fas fa-user-injured"; // Ícone diferente para pacientes
    $texto_empty_state_sidebar = "Nenhum paciente com conversa encontrada";
    $texto_empty_state_chat = "Escolha um paciente na lista ao lado para começar a conversar";
    $label_chat_header = ""; // Não usa "Dr." para paciente
    $campo_nome_conversa = 'nome_paciente';
    $avatar_class_sidebar = 'avatar-paciente'; // Nova classe para avatar de paciente
    $avatar_class_chat = 'avatar-paciente'; // Nova classe para avatar de paciente
    $icone_chat_info = 'fas fa-id-card'; // Exemplo: ícone para CPF

    // Buscar conversas do médico com informações dos pacientes
    $stmt = $pdo->prepare("
        SELECT c.id_conversa, p.cpf, p.nome AS nome_paciente, 
               (SELECT COUNT(*) FROM mensagens WHERE id_conversa = c.id_conversa AND remetente = 'paciente' AND lida = FALSE) AS mensagens_nao_lidas,
               (SELECT mensagem FROM mensagens WHERE id_conversa = c.id_conversa ORDER BY data_envio DESC LIMIT 1) AS ultima_mensagem,
               (SELECT data_envio FROM mensagens WHERE id_conversa = c.id_conversa ORDER BY data_envio DESC LIMIT 1) AS data_ultima_mensagem
        FROM conversas c
        JOIN paciente p ON c.id_paciente = p.cpf
        WHERE c.id_medico = ?
        ORDER BY data_ultima_mensagem DESC
    ");
    $stmt->execute([$id_usuario_logado]); // Usa o CRM do médico logado
    $conversas = $stmt->fetchAll();

    if (isset($_GET['conversa'])) {
        $id_conversa = $_GET['conversa'];
        $stmt = $pdo->prepare("SELECT * FROM conversas WHERE id_conversa = ? AND id_medico = ?");
        $stmt->execute([$id_conversa, $id_usuario_logado]);
        $conversa_atual = $stmt->fetch();
        
        if ($conversa_atual) {
            $stmt = $pdo->prepare("SELECT * FROM paciente WHERE cpf = ?");
            $stmt->execute([$conversa_atual['id_paciente']]);
            $perfil_conversa = $stmt->fetch(); // Detalhes do paciente para o chat header

            $stmt = $pdo->prepare("SELECT * FROM mensagens WHERE id_conversa = ? ORDER BY data_envio ASC");
            $stmt->execute([$id_conversa]);
            $mensagens_conversa = $stmt->fetchAll();
            
            // Marcar mensagens do paciente como lidas
            $stmt = $pdo->prepare("UPDATE mensagens SET lida = TRUE WHERE id_conversa = ? AND remetente = 'paciente'");
            $stmt->execute([$id_conversa]);
        } else {
            // Se a conversa não pertence ao médico logado ou não existe
            header("Location: chat_list.php?error=invalid_conversa");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Chat - Agende Já</title> <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Seus estilos CSS existentes */
        :root {
            --primary-gradient: linear-gradient(135deg, #11dadc, #007bff);
            --navbar-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            overflow: hidden;
        }

        /* Navbar fixa */
        .navbar-custom {
            background: var(--primary-gradient);
            height: var(--navbar-height);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar-brand img {
            filter: brightness(0) invert(1);
        }

        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 8px 16px !important;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }

        .login-select {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 20px;
        }

        .login-select option {
            background-color: #007bff;
            color: white;
        }

        /* Container principal */
        .main-container {
            margin-top: var(--navbar-height);
            height: calc(100vh - var(--navbar-height));
            display: flex;
        }

        /* Lista de conversas */
        .conversations-sidebar {
            width: 350px;
            background: white;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .conversations-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: white;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .conversa-item {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .conversa-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .conversa-item.conversa-ativa {
            background: linear-gradient(135deg, rgba(17, 218, 220, 0.1), rgba(0, 123, 255, 0.1));
            border-color: #11dadc;
        }

        .avatar-medico, .avatar-paciente { /* Estilo unificado para ambos os avatares */
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(17, 218, 220, 0.3);
        }

        .badge-unread {
            background: linear-gradient(135deg, #ff4757, #ff3742);
            color: white;
            border-radius: 12px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: bold;
        }

        /* Área do chat */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            background: white;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .message-wrapper {
            margin-bottom: 20px;
            display: flex;
        }

        .message-wrapper.paciente {
            justify-content: flex-end;
        }

        .message-wrapper.medico {
            justify-content: flex-start;
        }

        .message-card {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .message-card.paciente-message {
            background: var(--primary-gradient);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-card.medico-message {
            background: white;
            color: #333;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 11px;
            margin-top: 5px;
            opacity: 0.7;
        }

        /* Input de mensagem */
        .message-input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #e9ecef;
        }

        .message-input {
            border: 2px solid #e9ecef;
            border-radius: 25px;
            padding: 12px 20px;
            resize: none;
            transition: all 0.3s ease;
        }

        .message-input:focus {
            border-color: #11dadc;
            box-shadow: 0 0 0 3px rgba(17, 218, 220, 0.1);
        }

        .send-button {
            background: var(--primary-gradient);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(17, 218, 220, 0.3);
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(17, 218, 220, 0.4);
        }

        /* Estado vazio */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .conversations-sidebar {
                position: fixed;
                left: -350px;
                top: var(--navbar-height);
                z-index: 999;
                transition: left 0.3s ease;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }

            .conversations-sidebar.show {
                left: 0;
            }

            .chat-area {
                width: 100%;
            }

            .mobile-menu-btn {
                position: fixed;
                top: calc(var(--navbar-height) + 10px);
                left: 10px;
                z-index: 1001;
                background: var(--primary-gradient);
                border: none;
                border-radius: 50%;
                width: 45px;
                height: 45px;
                color: white;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            }
        }

        /* Scrollbar personalizada */
        .conversations-list::-webkit-scrollbar,
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .conversations-list::-webkit-scrollbar-track,
        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .conversations-list::-webkit-scrollbar-thumb,
        .chat-messages::-webkit-scrollbar-thumb {
            background: #11dadc;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="Home.php">
                <img src="imagens/logo.png" alt="Agende Já" height="40" class="me-2">
                <span class="fw-bold">Agende Já</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="pagAgendamento.php">
                            <i class="fas fa-calendar-plus me-1"></i>Agendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="meusDados.php">
                            <i class="fas fa-user me-1"></i>Meus Dados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="chat_list.php">
                            <i class="fas fa-comments me-1"></i>Chat
                        </a>
                    </li>
                    <li class="nav-item">
                        <select class="form-select login-select" onchange="window.location.href=this.value">
                            <option value="">Login</option>
                            <option value="loginMedico.php">Médico</option>
                            <option value="loginPaciente.php">Paciente</option>
                        </select>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <button class="mobile-menu-btn d-md-none" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="main-container">
        <div class="conversations-sidebar" id="conversationsSidebar">
            <div class="conversations-header">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="<?= $icone_sidebar ?> me-2"></i><?= $titulo_sidebar ?>
                </h5>
            </div>
            
            <div class="conversations-list">
                <?php if (!empty($conversas)): ?>
                    <?php foreach ($conversas as $conversa): ?>
                        <div class="conversa-item <?= isset($conversa_atual) && $conversa_atual['id_conversa'] == $conversa['id_conversa'] ? 'conversa-ativa' : '' ?>"
                             onclick="window.location.href='chat_list.php?conversa=<?= $conversa['id_conversa'] ?>'">
                            <div class="d-flex align-items-center">
                                <div class="<?= ($is_paciente ? $avatar_class_sidebar : ($is_medico ? 'avatar-paciente' : '')) ?> me-3">
                                    <?= strtoupper(substr($conversa[$campo_nome_conversa], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 fw-bold">
                                            <?= $label_chat_header ? $label_chat_header . ' ' : '' ?><?= htmlspecialchars($conversa[$campo_nome_conversa]) ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?= $conversa['data_ultima_mensagem'] ? date('H:i', strtotime($conversa['data_ultima_mensagem'])) : '' ?>
                                        </small>
                                    </div>
                                    <p class="text-muted small mb-0">
                                        <?= $conversa['ultima_mensagem'] ? 
                                            (strlen($conversa['ultima_mensagem']) > 35 ? 
                                                htmlspecialchars(substr($conversa['ultima_mensagem'], 0, 35)).'...' : 
                                                htmlspecialchars($conversa['ultima_mensagem'])) : 
                                            'Nenhuma mensagem ainda' ?>
                                    </p>
                                </div>
                                <?php if ($conversa['mensagens_nao_lidas'] > 0): ?>
                                    <span class="badge-unread"><?= $conversa['mensagens_nao_lidas'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <p class="text-muted"><?= $texto_empty_state_sidebar ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-area">
            <?php if ($conversa_atual && $perfil_conversa): ?>
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <div class="<?= $avatar_class_chat ?> me-3">
                            <?= strtoupper(substr($perfil_conversa['nome'], 0, 1)) ?>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">
                                <?= $label_chat_header ? $label_chat_header . ' ' : '' ?><?= htmlspecialchars($perfil_conversa['nome']) ?>
                            </h5>
                            <p class="text-muted small mb-0">
                                <i class="<?= $icone_chat_info ?> me-1"></i>
                                <?= htmlspecialchars($is_paciente ? $perfil_conversa['especialidade'] : $perfil_conversa['cpf']) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="chat-messages" id="chat-messages">
                    <?php foreach ($mensagens_conversa as $mensagem): ?>
                        <div class="message-wrapper <?= $mensagem['remetente'] ?>">
                            <div class="message-card <?= $mensagem['remetente'] ?>-message">
                                <p class="mb-0"><?= htmlspecialchars($mensagem['mensagem']) ?></p>
                                <div class="message-time">
                                    <?= date('H:i', strtotime($mensagem['data_envio'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="message-input-area">
                    <div class="d-flex align-items-end gap-3">
                        <div class="flex-grow-1">
                            <textarea class="form-control message-input" 
                                       id="message-input" 
                                       rows="2" 
                                       placeholder="Digite sua mensagem..."
                                       onkeypress="handleKeyPress(event)"></textarea>
                        </div>
                        <button type="button" class="send-button" id="send-button" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>

                <script>
                    const id_conversa = <?= json_encode($conversa_atual['id_conversa']) ?>; // Usar json_encode para garantir tipo correto
                    const tipo_usuario = '<?= $tipo_usuario_logado ?>';
                    const usuario_id = '<?= $id_usuario_logado ?>';
                    
                    const ws = new WebSocket('ws://localhost:8080');
                    const chatMessages = document.getElementById('chat-messages');
                    const messageInput = document.getElementById('message-input');

                    ws.onopen = function() {
                        console.log('Conectado ao servidor WebSocket');
                        ws.send(JSON.stringify({
                            type: 'init',
                            tipo: tipo_usuario,
                            id: usuario_id,
                            conversa_id: id_conversa
                        }));
                    };

                    ws.onmessage = function(event) {
                        const data = JSON.parse(event.data);
                        if (data.type === 'message') {
                            addMessageToChat(data);
                        }
                    };

                    function sendMessage() {
                        const message = messageInput.value.trim();
                        if (message) {
                            ws.send(JSON.stringify({
                                type: 'message',
                                message: message,
                                // Envia o id_conversa e remetente para o servidor WebSocket
                                conversa_id: id_conversa,
                                remetente: tipo_usuario
                            }));
                            
                            addMessageToChat({
                                sender: tipo_usuario,
                                message: message,
                                time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            
                            messageInput.value = '';
                            messageInput.style.height = 'auto';
                        }
                    }

                    function addMessageToChat(data) {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message-wrapper ${data.sender}`;
                        messageDiv.innerHTML = `
                            <div class="message-card ${data.sender}-message">
                                <p class="mb-0">${data.message}</p>
                                <div class="message-time">
                                    ${data.time}
                                </div>
                            </div>
                        `;
                        chatMessages.appendChild(messageDiv);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }

                    function handleKeyPress(event) {
                        if (event.key === 'Enter' && !event.shiftKey) {
                            event.preventDefault();
                            sendMessage();
                        }
                    }

                    messageInput.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                    });

                    window.onload = function() {
                        if (chatMessages) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    };
                </script>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h4 class="mb-2">Selecione uma conversa</h4>
                    <p class="text-muted"><?= $texto_empty_state_chat ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('conversationsSidebar');
            sidebar.classList.toggle('show');
        }

        // Fechar sidebar ao clicar fora (mobile)
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('conversationsSidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuBtn.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>