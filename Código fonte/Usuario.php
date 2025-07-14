<!-- <?php
    // $servername = "localhost";
    // $usuario = "root";
    // $senha = "";
    // $banco = "agendeja";

    // $conexao = new mysqli($servername, $usuario, $senha,$banco);
    // if ($conexao->connect_error) {
    //     die("Erro de conexao! ". $conexao->connecr_error);
    // }
    // if ($_SERVER["REQUEST_METHOD"] == "GET") {
    //     $sql = "SELECT * FROM consulta WHERE cpf_paciente = 1";
    //     $result = $conexao->query($sql);
    
    //     // if (!$result) {
    //     //     die("Erro na consulta: " . $conexao->error); // Mostra o erro do MySQL
    //     // }
    // }
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dados Pessoais e Consultas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="estiloNovo.css" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <main class="container">
        <h1 class="titulo-principal">Área do Paciente</h1>

        <div class="grid-container">
            <!-- Card de Dados Pessoais -->
            <div class="card card-dados">
                <div class="perfil-container">
                    <div class="avatar">
                        <span>MS</span>
                    </div>
                    <h2 class="titulo-card">Dados Pessoais</h2>
                    <button class="botao-editar" id="botao-editar">
                        <i class="fas fa-pencil-alt"></i> Editar
                    </button>
                </div>

                <div class="dados-lista">
                    <!-- Campos não editáveis -->
                    <div class="dado-item">
                        <i class="fas fa-id-card icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">CPF:</p>
                            <p class="dado-valor">123.456.789-00</p>
                        </div>
                    </div>

                    <div class="dado-item">
                        <i class="fas fa-user icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">Nome:</p>
                            <p class="dado-valor">Maria Silva</p>
                        </div>
                    </div>

                    <!-- Campos editáveis -->
                    <div class="dado-item dado-editavel">
                        <i class="fas fa-map-marker-alt icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">Endereço:</p>
                            <p class="dado-valor dado-editavel-conteudo">Av. Paulista, 1000 - São Paulo, SP</p>
                        </div>
                    </div>

                    <div class="dado-item dado-editavel">
                        <i class="fas fa-envelope icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">E-mail:</p>
                            <p class="dado-valor dado-editavel-conteudo">maria.silva@exemplo.com</p>
                        </div>
                    </div>

                    <div class="dado-item dado-editavel">
                        <i class="fas fa-phone icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">Telefone:</p>
                            <p class="dado-valor dado-editavel-conteudo">(11) 98765-4321</p>
                        </div>
                    </div>

                    <div class="dado-item dado-editavel">
                        <i class="fas fa-heartbeat icone"></i>
                        <div class="dado-conteudo">
                            <p class="dado-label">Convênio:</p>
                            <p class="dado-valor dado-editavel-conteudo">Amil Saúde</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Consultas Agendadas -->
            <div class="card card-consultas">
                <h2 class="titulo-card">Consultas Agendadas</h2>

                <div class="consultas-lista">
                    <?php while ($row = $result->fetch_assoc()){ 
                        $sqlInfoMedico = "SELECT nome, especialidade FROM medico WHERE crm=".$row['crm_medico'];
                        $resultadoInfoMed = $conexao->query($sqlInfoMedico);
                        while($r=$resultadoInfoMed->fetch_assoc()){
                            $especialidade=$r['especialidade'];
                            $nome=$r['nome'];
                        }
                    ?>
                    <div class="consulta-item">
                        <div class="consulta-info">
                            <h3 class="consulta-especialidade"><?=$especialidade?></h3>
                            <p class="consulta-medico"><?=$nome?></p>

                            <div class="consulta-detalhes">
                                <div class="consulta-data">
                                    <i class="fas fa-calendar-alt icone-pequeno"></i>
                                    <span class="dado-valor"><?= $row['data_hora'] ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="consulta-acoes">
                            <span class="badge badge-pendente">Pendente</span>
                            <!-- Botão de deletar consulta -->
                            <a href="deletar_consulta.php?id_consulta=<?= $row['id_consulta'] ?>" class="botao botao-deletar" onclick="return confirm('Tem certeza que deseja cancelar esta consulta?');">
                                <i class="fas fa-trash-alt"></i> Cancelar
                            </a>
                        </div>
                    </div>
                    <?php } ?>    
                </div>

                <a class="botao botao-full" href="pagAgendamento.php">Agendar Nova Consulta</a>
            </div>
        </div>
    </main>
    
    <!-- Script para edição de dados -->
    <script>
        document.getElementById('botao-editar').addEventListener('click', function() {
            const editaveis = document.querySelectorAll('.dado-editavel-conteudo');
            const estaEditando = this.classList.toggle('editando-ativo');
            
            // if (estaEditando) {
            //     this.innerHTML = '<i class="fas fa-save"></i> Salvar';
            //     editaveis.forEach(campo => {
            //         campo.setAttribute('contenteditable', 'true');
            //         campo.focus();
            //     });
            // } else {
            //     this.innerHTML = '<i class="fas fa-pencil-alt"></i> Editar';
                editaveis.forEach(campo => {
                    campo.setAttribute('contenteditable', 'false');
                    // Aqui você pode adicionar código para salvar no backend
                });
                alert('Alterações salvas com sucesso!');
            }
        });
    </script>
</body>
</html> --!>
