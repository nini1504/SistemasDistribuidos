<?php
// Inclui o autoloader do Composer. O Ratchet é uma biblioteca que precisa ser instalada via Composer.
// O Composer é um gerenciador de dependências para PHP.
require 'vendor/autoload.php';

// Inclui o arquivo de configuração do banco de dados (provavelmente $pdo).
// É crucial que 'db.php' defina a variável $pdo como global ou que seja acessível por outros meios.
include 'db.php';

// Importa as classes necessárias da biblioteca Ratchet.
// MessageComponentInterface: Interface principal para componentes de mensagem.
// ConnectionInterface: Representa uma conexão individual de um cliente.
// IoServer: Servidor de E/S (Input/Output) que escuta por novas conexões.
// HttpServer: Camada para lidar com requisições HTTP (necessário para o handshake WebSocket).
// WsServer: Camada para lidar especificamente com o protocolo WebSocket.
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

/**
 * Classe Chat implementa MessageComponentInterface.
 * Esta classe define o comportamento do seu servidor WebSocket.
 * Ela é o coração da sua aplicação de chat em tempo real.
 */
class Chat implements MessageComponentInterface {
    // SplObjectStorage é uma coleção que mapeia objetos a dados arbitrários.
    // Aqui, ele é usado para armazenar todas as conexões de clientes ativas.
    protected $clients;

    // Um array associativo para manter informações sobre as conversas ativas.
    // A chave será o resourceId da conexão (um ID único para cada conexão),
    // e o valor será um array com 'tipo' (paciente/medico), 'id' (CPF/CRM) e 'conversa_id'.
    protected $conversasAtivas;

    /**
     * Construtor da classe. Inicializa as coleções de clientes e conversas ativas.
     */
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->conversasAtivas = [];
    }

    /**
     * onOpen é chamado quando um novo cliente se conecta ao servidor WebSocket.
     * @param ConnectionInterface $conn O objeto de conexão para o cliente recém-conectado.
     */
    public function onOpen(ConnectionInterface $conn) {
        // Anexa a nova conexão ao SplObjectStorage para que possamos gerenciá-la.
        $this->clients->attach($conn);
        echo "Nova conexão! ({$conn->resourceId})\n"; // Loga no console do servidor
    }

    /**
     * onMessage é chamado quando o servidor recebe uma mensagem de um cliente conectado.
     * @param ConnectionInterface $from O objeto de conexão do cliente que enviou a mensagem.
     * @param string $msg A mensagem recebida do cliente (geralmente um JSON string).
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        // Decodifica a mensagem JSON recebida do cliente.
        $data = json_decode($msg, true);
        
        // --- Lógica para o Tipo de Mensagem 'init' ---
        // 'init' (inicialização) é a primeira mensagem que o cliente envia ao se conectar
        // para informar ao servidor quem ele é e qual conversa ele está visualizando.
        if ($data['type'] === 'init') {
            // Armazena as informações do cliente (tipo de usuário, ID e ID da conversa)
            // associadas ao resourceId único da conexão. Isso permite que o servidor
            // saiba quem é quem e a qual conversa cada conexão pertence.
            $this->conversasAtivas[$from->resourceId] = [
                'tipo' => $data['tipo'],        // 'paciente' ou 'medico'
                'id' => $data['id'],            // CPF do paciente ou CRM do médico
                'conversa_id' => $data['conversa_id'] // ID da conversa ativa no momento
            ];
            echo "Conexão {$from->resourceId} inicializada como {$data['tipo']} (ID: {$data['id']}) na conversa {$data['conversa_id']}.\n";
            return; // Retorna para não processar como uma mensagem de chat normal
        }

        // --- Lógica para o Tipo de Mensagem 'message' ---
        // Se o tipo de mensagem não for 'init', assume-se que é uma mensagem de chat.
        if ($data['type'] === 'message') {
            // Recupera as informações da conversa ativa para a conexão atual.
            $conversaInfo = $this->conversasAtivas[$from->resourceId];
            $conversaId = $conversaInfo['conversa_id'];
            
            // --- Salvar mensagem no banco de dados ---
            // Acessa a conexão PDO globalmente (definida em db.php).
            global $pdo;
            $stmt = $pdo->prepare("INSERT INTO mensagens (id_conversa, remetente, mensagem) VALUES (?, ?, ?)");
            $stmt->execute([
                $conversaId,                // ID da conversa onde a mensagem será salva
                $conversaInfo['tipo'],      // 'paciente' ou 'medico' (quem enviou)
                $data['message']            // O conteúdo da mensagem
            ]);
            echo "Mensagem de {$conversaInfo['tipo']} para conversa {$conversaId}: '{$data['message']}' salva no DB.\n";
            
            // --- Enviar mensagem para o outro participante na mesma conversa ---
            // Itera sobre todas as conexões de clientes ativas no servidor.
            foreach ($this->clients as $client) {
                // Verifica se o cliente na iteração atual tem informações de conversa (ou seja, já se inicializou).
                if (isset($this->conversasAtivas[$client->resourceId])) {
                    $clientInfo = $this->conversasAtivas[$client->resourceId];
                    
                    // Verifica se o cliente está na mesma conversa E não é o remetente original da mensagem.
                    if ($clientInfo['conversa_id'] == $conversaId && $client !== $from) {
                        // Envia a mensagem para o outro participante da conversa.
                        $client->send(json_encode([
                            'type' => 'message',
                            'message' => $data['message'],
                            'sender' => $conversaInfo['tipo'], // Quem enviou a mensagem (paciente ou médico)
                            'time' => date('H:i') // Hora atual da mensagem
                        ]));
                        echo "Mensagem retransmitida para cliente {$client->resourceId} na conversa {$conversaId}.\n";
                    }
                }
            }
        }
    }

    /**
     * onClose é chamado quando um cliente se desconecta do servidor.
     * @param ConnectionInterface $conn O objeto de conexão do cliente que se desconectou.
     */
    public function onClose(ConnectionInterface $conn) {
        // Remove as informações da conversa ativa para a conexão que foi fechada.
        unset($this->conversasAtivas[$conn->resourceId]);
        // Remove a conexão do SplObjectStorage.
        $this->clients->detach($conn);
        echo "Conexão {$conn->resourceId} desconectada\n"; // Loga no console do servidor
    }

    /**
     * onError é chamado quando ocorre um erro com uma conexão.
     * @param ConnectionInterface $conn O objeto de conexão onde ocorreu o erro.
     * @param \Exception $e O objeto de exceção que descreve o erro.
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erro na conexão {$conn->resourceId}: {$e->getMessage()}\n"; // Loga o erro
        $conn->close(); // Fecha a conexão problemática para evitar mais problemas.
    }
}

// --- Início do Servidor WebSocket ---
// Cria um servidor IoServer.
// Ele é o "ouvido" principal que aceita as conexões.
$server = IoServer::factory(
    new HttpServer( // Encapsula o WebSocket em uma camada HTTP (para o handshake)
        new WsServer( // Encapsula sua lógica de chat (a classe Chat) em um servidor WebSocket
            new Chat() // Instância da sua classe Chat, que gerencia as mensagens e conexões
        )
    ),
    8080 // Porta em que o servidor WebSocket irá escutar (ex: ws://localhost:8080)
);

// Inicia o servidor e o mantém rodando.
// Esta linha bloqueará a execução do script até que o servidor seja parado.
$server->run();

?>