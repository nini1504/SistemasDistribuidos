package mensageria;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.sun.net.httpserver.HttpServer;
import mensageria.models.CardapioResponse;
import mensageria.models.ItemMenu;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.util.Map;
import java.util.Scanner;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.stream.Collectors;

public class ClienteRestaurante {
    private static final int PORTA_OUVINTE = 8083; // porta que o cliente vai usar pra se comunicar
    //private static final String URL_SERVIDOR = "http://localhost:8080";
    private static final String NOME_SERVICO_RESTAURANTE = "servidor do restaurante"; // nome do serviço no NameServer
    private static final ObjectMapper mapper = new ObjectMapper(); // usado pra lidar com JSON
    private static final HttpClient httpClient = HttpClient.newBuilder()
            .version(HttpClient.Version.HTTP_1_1) // http 1.1 mesmo
            .build();
    private static final String API_CARDAPIO_URL = "http://localhost:1880/cardapioRest"; // api no node-red

    private static Map<Integer, ItemMenu> cardapioAtual = new java.util.HashMap<>(); // guardo o cardápio aqui

    public static void main(String[] args) {

        int portaOuvinte = 8083; // valor padrão
        if (args.length > 0) {
            try {
                portaOuvinte = Integer.parseInt(args[0]); // se passar argumento, tenta usar ele
            } catch (NumberFormatException e) {
                System.err.println("Porta inválida, usando 8083");
            }
        }

        final int PORTA_OUVINTE = portaOuvinte;

        ExecutorService executor = Executors.newSingleThreadExecutor();

        try {
            // primeiro preciso achar o servidor no NameServer
            Servico servicoRestaurante;
            try {
                System.out.println("Buscando o '" + NOME_SERVICO_RESTAURANTE + "' no Servidor de Nomes...");
                servicoRestaurante = NameServiceClient.buscarServico(NOME_SERVICO_RESTAURANTE);
                if (servicoRestaurante == null) {
                    System.err.println("ERRO FATAL: Serviço '" + NOME_SERVICO_RESTAURANTE + "' não encontrado. Verifique se o ServidorRestaurante e o NameServer estão no ar.");
                    return;
                }
                System.out.println("Serviço encontrado em: " + servicoRestaurante.getHost() + ":" + servicoRestaurante.getPorta());
            } catch (IOException e) {
                System.err.println("ERRO FATAL: Não foi possível comunicar com o Servidor de Nomes.");
                System.err.println("Detalhes: " + e.getMessage());
                return;
            }

            // aqui monto a url base do servidor que foi encontrado
            final String URL_SERVIDOR = "http://" + servicoRestaurante.getHost() + ":" + servicoRestaurante.getPorta();

            // agora pego o cardápio da API
            System.out.println("Obtendo cardápio da API...");
            HttpRequest cardapioRequest = HttpRequest.newBuilder().uri(URI.create(API_CARDAPIO_URL)).GET().build();
            HttpResponse<String> cardapioResponse = httpClient.send(cardapioRequest, HttpResponse.BodyHandlers.ofString());

            if (cardapioResponse.statusCode() == 200) {
                // uso o wrapper CardapioResponse porque o retorno é um objeto que contém os itens
                CardapioResponse wrapper = mapper.readValue(cardapioResponse.body(), CardapioResponse.class);
                for (ItemMenu item : wrapper.getItens()) {
                    cardapioAtual.put(item.id, item); // coloco cada item no map
                }
                System.out.println("Cardápio obtido com sucesso. Menu do Restaurante:");
                cardapioAtual.values().forEach(System.out::println);
            } else {
                System.out.println("Falha ao obter o cardápio. Status: " + cardapioResponse.statusCode());
                return;
            }

            // parte do pedido: o usuário digita no console
            Scanner sc = new Scanner(System.in);
            String userInput;
            while (true) {
                System.out.print("\nSeu pedido (ID-QUANTIDADE,ID-QUANTIDADE ou 'exit' para sair): ");
                userInput = sc.nextLine();

                if ("exit".equalsIgnoreCase(userInput)) {
                    break; // sai do loop
                }

                // mando o pedido pro servidor
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
            executor.shutdown(); // fecha o executor no final
            System.out.println("Conexão encerrada.");
        }
    }
}
