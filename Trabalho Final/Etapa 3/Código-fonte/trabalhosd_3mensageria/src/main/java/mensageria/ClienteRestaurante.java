package mensageria;

import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.sun.net.httpserver.HttpServer;
import com.sun.net.httpserver.HttpExchange;
import mensageria.models.CardapioResponse;
import mensageria.models.ItemMenu;
import mensageria.models.PedidoItem;
import mensageria.models.Pedido;

import java.io.*;
import java.net.*;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.Scanner;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;
import java.util.stream.Collectors;

public class ClienteRestaurante {
    private static final int PORTA_OUVINTE = 8083;
    private static final String URL_SERVIDOR = "http://localhost:8080";

    private static final ObjectMapper mapper = new ObjectMapper();
    private static final HttpClient httpClient = HttpClient.newBuilder()
            .version(HttpClient.Version.HTTP_1_1)
            .build();
    private static final String API_CARDAPIO_URL = "http://localhost:1880/cardapioRest";

    private static Map<Integer, ItemMenu> cardapioAtual = new java.util.HashMap<>();

    public static void main(String[] args) {
        ExecutorService executor = Executors.newSingleThreadExecutor();
        executor.submit(() -> ouvirPorEntregas());

        try {
            System.out.println("Obtendo cardápio da API...");
            HttpRequest cardapioRequest = HttpRequest.newBuilder().uri(URI.create(API_CARDAPIO_URL)).GET().build();
            HttpResponse<String> cardapioResponse = httpClient.send(cardapioRequest, HttpResponse.BodyHandlers.ofString());

            if (cardapioResponse.statusCode() == 200) {
                // Lógica de desserialização CORRIGIDA para usar o wrapper
                CardapioResponse wrapper = mapper.readValue(cardapioResponse.body(), CardapioResponse.class);
                for (ItemMenu item : wrapper.getItens()) {
                    cardapioAtual.put(item.id, item);
                }
                System.out.println("Cardápio obtido com sucesso. Menu do Restaurante:");
                cardapioAtual.values().forEach(System.out::println);
            } else {
                System.out.println("Falha ao obter o cardápio. Status: " + cardapioResponse.statusCode());
                return;
            }

            Scanner sc = new Scanner(System.in);
            String userInput;
            while (true) {
                System.out.print("\nSeu pedido (ID-QUANTIDADE,ID-QUANTIDADE ou 'exit' para sair): ");
                userInput = sc.nextLine();

                if ("exit".equalsIgnoreCase(userInput)) {
                    break;
                }

                HttpRequest pedidoRequest = HttpRequest.newBuilder()
                        .uri(URI.create(URL_SERVIDOR + "/pedido"))
                        .header("Content-Type", "text/plain")
                        .POST(HttpRequest.BodyPublishers.ofString(userInput))
                        .build();

                HttpResponse<String> respostaServidor = httpClient.send(pedidoRequest, HttpResponse.BodyHandlers.ofString());
                System.out.println("\n--- Resposta do Servidor ---");
                System.out.println(respostaServidor.body());
            }

            sc.close();

        } catch (IOException e) {
            System.err.println("Erro na conexão ou comunicação: " + e.getMessage());
        } catch (InterruptedException e) {
            Thread.currentThread().interrupt();
        } finally {
            executor.shutdown();
            System.out.println("Conexão encerrada.");
        }
    }

    private static void ouvirPorEntregas() {
        try {
            HttpServer server = HttpServer.create(new InetSocketAddress(PORTA_OUVINTE), 0);
            server.createContext("/entrega", exchange -> {
                if ("POST".equals(exchange.getRequestMethod())) {
                    String entregaMsg = new BufferedReader(new InputStreamReader(exchange.getRequestBody())).lines().collect(Collectors.joining("\n"));
                    System.out.println("\n>>> Garçom chegou! Recebendo entrega:");
                    System.out.println(entregaMsg);

                    String response = "Entrega recebida!";
                    exchange.sendResponseHeaders(200, response.length());
                    OutputStream os = exchange.getResponseBody();
                    os.write(response.getBytes());
                    os.close();
                } else {
                    exchange.sendResponseHeaders(405, -1);
                }
            });
            server.start();
            System.out.println("Cliente: Aguardando entregas na porta " + PORTA_OUVINTE + "...");
        } catch (IOException e) {
            System.err.println("Erro ao iniciar o servidor de entrega do cliente: " + e.getMessage());
        }
    }
}