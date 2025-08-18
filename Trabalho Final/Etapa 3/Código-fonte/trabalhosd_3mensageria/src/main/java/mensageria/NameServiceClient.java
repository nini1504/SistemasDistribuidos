package mensageria;

import com.fasterxml.jackson.databind.ObjectMapper;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.PrintWriter;
import java.net.Socket;
import java.util.HashMap;
import java.util.Map;

public class NameServiceClient {
    private static final String NAMESERVER_HOST = "localhost";
    private static final int NAMESERVER_PORT = 12344;
    private static final ObjectMapper objectMapper = new ObjectMapper();

    /**
     * Registra um serviço no NameServer.
     * @param servico O objeto Servico a ser registrado.
     * @throws IOException Se houver um erro de comunicação.
     */
    public static void registrarServico(Servico servico) throws IOException {
        try (Socket socket = new Socket(NAMESERVER_HOST, NAMESERVER_PORT);
             PrintWriter out = new PrintWriter(socket.getOutputStream(), true)) {
            Map<String, Object> request = new HashMap<>();
            request.put("acao", "registrar");
            request.put("servico", servico);
            out.println(objectMapper.writeValueAsString(request));
        }
    }

    /**
     * Busca um serviço no NameServer.
     * @param nomeServico O nome do serviço a ser buscado.
     * @return Um objeto Servico se encontrado, ou null caso contrário.
     * @throws IOException Se houver um erro de comunicação.
     */
    public static Servico buscarServico(String nomeServico) throws IOException {
        try (Socket socket = new Socket(NAMESERVER_HOST, NAMESERVER_PORT);
             PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
             BufferedReader in = new BufferedReader(new InputStreamReader(socket.getInputStream()))) {

            // Monta a requisição de busca
            Map<String, Object> request = new HashMap<>();
            request.put("acao", "buscar");
            request.put("nomeServico", nomeServico);
            out.println(objectMapper.writeValueAsString(request));

            // Lê a resposta do servidor
            String jsonResponse = in.readLine();
            if (jsonResponse == null) {
                return null;
            }

            Map<String, Object> response = objectMapper.readValue(jsonResponse, Map.class);

            if ("sucesso".equals(response.get("status"))) {
                // Converte o mapa aninhado 'servico' para um objeto Servico
                return objectMapper.convertValue(response.get("servico"), Servico.class);
            } else {
                return null;
            }
        }
    }
}
