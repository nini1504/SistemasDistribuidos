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
    private static final int PORTA_NAMESERVER = 12344; // Porta fixa para o Servidor de Nomes
    private static final ConcurrentHashMap<String, Servico> SERVICOS_REGISTRADOS = new ConcurrentHashMap<>();
    private static final ObjectMapper objectMapper = new ObjectMapper();

    public static void main(String[] args) {
        System.out.println("Servidor de Nomes iniciado na porta " + PORTA_NAMESERVER);
        try (ServerSocket serverSocket = new ServerSocket(PORTA_NAMESERVER)) {
            while (true) {
                Socket clientSocket = serverSocket.accept();
                new Thread(() -> handleRequest(clientSocket)).start();
            }
        } catch (Exception e) {
            System.err.println("Erro no Servidor de Nomes: " + e.getMessage());
            e.printStackTrace();
        }
    }

    private static void handleRequest(Socket clientSocket) {
        try (BufferedReader in = new BufferedReader(new InputStreamReader(clientSocket.getInputStream()));
             PrintWriter out = new PrintWriter(clientSocket.getOutputStream(), true)) {

            String jsonRequest = in.readLine();
            if (jsonRequest == null) {
                System.out.println("Conexão encerrada pelo cliente.");
                return;
            }

            Map<String, Object> request = objectMapper.readValue(jsonRequest, new TypeReference<Map<String, Object>>() {});
            String acao = (String) request.get("acao");

            Map<String, Object> resposta = new HashMap<>();

            if ("registrar".equals(acao)) {
                // O Jackson consegue converter um Map para a classe Servico diretamente
                Servico servico = objectMapper.convertValue(request.get("servico"), Servico.class);
                SERVICOS_REGISTRADOS.put(servico.getNome(), servico);
                System.out.println("Servidor de Nomes: Serviço registrado -> " + servico.getNome() + " em " + servico.getHost() + ":" + servico.getPorta());
                resposta.put("status", "sucesso");
            } else if ("buscar".equals(acao)) {
                String nomeServico = (String) request.get("nomeServico");
                Servico servico = SERVICOS_REGISTRADOS.get(nomeServico);
                if (servico != null) {
                    resposta.put("status", "sucesso");
                    resposta.put("servico", servico);
                    System.out.println("Servidor de Nomes: Busca por " + nomeServico + " encontrada.");
                } else {
                    resposta.put("status", "erro");
                    resposta.put("mensagem", "Serviço não encontrado.");
                    System.out.println("Servidor de Nomes: Busca por " + nomeServico + " não encontrada.");
                }
            } else {
                resposta.put("status", "erro");
                resposta.put("mensagem", "Ação inválida.");
                System.out.println("Servidor de Nomes: Ação inválida recebida.");
            }

            out.println(NetworkUtils.serializeToJson(resposta));
        } catch (Exception e) {
            System.err.println("Erro ao processar a requisição: " + e.getMessage());
            e.printStackTrace();
        } finally {
            try {
                if (clientSocket != null) {
                    clientSocket.close();
                }
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }
}