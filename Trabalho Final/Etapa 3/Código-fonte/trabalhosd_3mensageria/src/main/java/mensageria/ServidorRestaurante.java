package mensageria;

import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import mensageria.models.ItemMenu;
import mensageria.models.Pedido;
import mensageria.models.PedidoItem;
import mensageria.models.ListaDeEspera;
import mensageria.models.CardapioResponse;
import com.sun.net.httpserver.HttpServer;
import com.sun.net.httpserver.HttpExchange;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.net.URI;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.stream.Collectors;

public class ServidorRestaurante {

    private static final int PORTA_SERVIDOR = 8080;
    private static final String API_CARDAPIO_URL = "http://localhost:1880/cardapioRest";

    private static final Map<Integer, ItemMenu> cardapio = new HashMap<>();
    private static final ListaDeEspera listaDeEspera = new ListaDeEspera();
    private static final AtomicInteger proximoIdPedido = new AtomicInteger(1);
    private static final ObjectMapper mapper = new ObjectMapper();
    private static final HttpClient httpClient = HttpClient.newHttpClient();

    public static void main(String[] args) {
        if (!carregarCardapioDaAPI()) {
            System.err.println("Erro fatal: Não foi possível carregar o cardápio. Encerrando.");
            return;
        }

        try {
            HttpServer server = HttpServer.create(new InetSocketAddress(PORTA_SERVIDOR), 0);
            System.out.println("Servidor aguardando requisições HTTP na porta " + PORTA_SERVIDOR + "...");
            System.out.println("--- Restaurante Operacional ---");

            server.createContext("/cardapio", exchange -> {
                if ("GET".equals(exchange.getRequestMethod())) {
                    String response = cardapio.values().stream()
                            .map(ItemMenu::toString)
                            .collect(Collectors.joining("\n"));
                    exchange.sendResponseHeaders(200, response.length());
                    OutputStream os = exchange.getResponseBody();
                    os.write(response.getBytes());
                    os.close();
                } else {
                    exchange.sendResponseHeaders(405, -1);
                }
            });

            server.createContext("/pedido", exchange -> {
                if ("POST".equals(exchange.getRequestMethod())) {
                    String pedidoStr = new BufferedReader(new InputStreamReader(exchange.getRequestBody())).lines().collect(Collectors.joining("\n"));
                    System.out.println("\nPedido HTTP recebido: " + pedidoStr);

                    String resposta = processarEPorNaFila(pedidoStr, exchange.getRemoteAddress().getAddress().getHostAddress(), exchange.getRemoteAddress().getPort());

                    exchange.sendResponseHeaders(200, resposta.length());
                    OutputStream os = exchange.getResponseBody();
                    os.write(resposta.getBytes());
                    os.close();
                } else {
                    exchange.sendResponseHeaders(405, -1);
                }
            });

            server.setExecutor(null);
            server.start();

        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private static boolean carregarCardapioDaAPI() {
        System.out.println("Carregando cardápio da API...");
        try {
            HttpClient httpClient = HttpClient.newBuilder()
                    .version(HttpClient.Version.HTTP_1_1) // força HTTP/1.1
                    .build();

            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create("http://127.0.0.1:1880/cardapioRest")) // usa IPv4
                    .header("Accept", "application/json")
                    .GET()
                    .build();

            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

            if (response.statusCode() == 200) {
                // ✅ agora usa o wrapper
                CardapioResponse cardapioResponse = mapper.readValue(response.body(), CardapioResponse.class);

                for (ItemMenu item : cardapioResponse.getItens()) {
                    cardapio.put(item.getId(), item);
                }

                System.out.println("Cardápio carregado com sucesso!");
                return true;
            } else {
                System.err.println("Falha ao carregar cardápio. Status: " + response.statusCode());
                return false;
            }
        } catch (Exception e) {
            System.err.println("Erro ao conectar à API de cardápio: " + e.getMessage());
            return false;
        }
    }
    private static String processarEPorNaFila(String pedidoStr, String clienteHost, int clientePorta) {
        try {
            List<PedidoItem> itensPedidos = new ArrayList<>();
            double valorTotal = 0.0;
            String[] itensStr = pedidoStr.split(",");

            for (String itemStr : itensStr) {
                String[] partes = itemStr.split("-");
                int id = Integer.parseInt(partes[0].trim());
                int quantidade = Integer.parseInt(partes[1].trim());

                ItemMenu itemCardapio = cardapio.get(id);
                if (itemCardapio == null) {
                    return "Item com ID " + id + " não encontrado.";
                }
                if (quantidade <= 0) {
                    return "Quantidade para o item " + id + " deve ser maior que zero.";
                }

                itensPedidos.add(new PedidoItem(id, quantidade));
                valorTotal += itemCardapio.getPreco() * quantidade;
            }

            Pedido novoPedido = new Pedido("Cliente", itensPedidos, clienteHost, clientePorta);
            novoPedido.setIdPedido(proximoIdPedido.getAndIncrement());
            novoPedido.setValorTotal(valorTotal);

            listaDeEspera.adicionarPedido(novoPedido);
            System.out.println("Pedido ID " + novoPedido.getIdPedido() + " adicionado à lista de espera.");
            System.out.println("Detalhes do pedido: " + novoPedido.getIdPedido() + " - Valor Total: " + String.format("%.2f", novoPedido.getValorTotal()));

            return "Pedido recebido com sucesso! ID do seu pedido: " + novoPedido.getIdPedido() + ". Valor total: R$" + String.format("%.2f", novoPedido.getValorTotal());

        } catch (Exception e) {
            System.err.println("Erro ao processar pedido: " + e.getMessage());
            return "Formato do pedido inválido. Use ID-QUANTIDADE.";
        }
    }
}