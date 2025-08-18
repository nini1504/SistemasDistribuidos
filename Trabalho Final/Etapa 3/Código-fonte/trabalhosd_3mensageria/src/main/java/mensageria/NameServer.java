package mensageria;

import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.PrintWriter;
import java.net.ServerSocket;
import java.net.Socket;
import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;

public class NameServer {
    private static final int PORTA_NAMESERVER = 12344; // porta fixa onde o servidor de nomes vai rodar
    private static final ConcurrentHashMap<String, Servico> SERVICOS_REGISTRADOS = new ConcurrentHashMap<>(); // aqui guardo todos os serviços registrados
    private static final ObjectMapper objectMapper = new ObjectMapper(); // usado pra converter json em objeto java e vice-versa

    public static void main(String[] args) {
        System.out.println("Servidor de Nomes iniciado na porta " + PORTA_NAMESERVER);
        try (ServerSocket serverSocket = new ServerSocket(PORTA_NAMESERVER)) {
            // servidor fica rodando infinito aceitando conexões
            while (true) {
                Socket clientSocket = serverSocket.accept(); // quando um cliente conecta
                new Thread(() -> handleRequest(clientSocket)).start(); // trato cada requisição em uma thread separada
            }
        } catch (Exception e) {
            System.err.println("Erro no Servidor de Nomes: " + e.getMessage());
            e.printStackTrace();
        }
    }

    private static void handleRequest(Socket clientSocket) {
        try (BufferedReader in = new BufferedReader(new InputStreamReader(clientSocket.getInputStream()));
             PrintWriter out = new PrintWriter(clientSocket.getOutputStream(), true)) {

            String jsonRequest = in.readLine(); // leio a requisição em json
            if (jsonRequest == null) {
                System.out.println("Conexão encerrada pelo cliente.");
                return;
            }

            // converto o json em um map genérico
            Map<String, Object> request = objectMapper.readValue(jsonRequest, new TypeReference<Map<String, Object>>() {});
            String acao = (String) request.get("acao"); // pego qual ação o cliente quer

            Map<String, Object> resposta = new HashMap<>();

            if ("registrar".equals(acao)) {
                // pego os dados do serviço e transformo num objeto Servico
                Servico servico = objectMapper.convertValue(request.get("servico"), Servico.class);
                SERVICOS_REGISTRADOS.put(servico.getNome(), servico); // salvo no mapa global
                System.out.println("Servidor de Nomes: Serviço registrado -> " + servico.getNome() + " em " + servico.getHost() + ":" + servico.getPorta());
                resposta.put("status", "sucesso");
            } else if ("buscar".equals(acao)) {
                String nomeServico = (String) request.get("nomeServico");
                Servico servico = SERVICOS_REGISTRADOS.get(nomeServico);
                if (servico != null) {
                    resposta.put("status", "sucesso");
                    resposta.put("servico", servico); // devolvo o serviço encontrado
                    System.out.println("Servidor de Nomes: Busca por " + nomeServico + " encontrada.");
                } else {
                    resposta.put("status", "erro");
                    resposta.put("mensagem", "Serviço não encontrado.");
                    System.out.println("Servidor de Nomes: Busca por " + nomeServico + " não encontrada.");
                }
            } else {
                // se mandarem uma ação que não existe
                resposta.put("status", "erro");
                resposta.put("mensagem", "Ação inválida.");
                System.out.println("Servidor de Nomes: Ação inválida recebida.");
            }

            // mando a resposta em json de volta pro cliente
            out.println(NetworkUtils.serializeToJson(resposta));
        } catch (Exception e) {
            System.err.println("Erro ao processar a requisição: " + e.getMessage());
            e.printStackTrace();
        } finally {
            try {
                if (clientSocket != null) {
                    clientSocket.close(); // fecho a conexão no final
                }
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }
}
