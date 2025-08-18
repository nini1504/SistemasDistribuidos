package mensageria;

import com.fasterxml.jackson.databind.ObjectMapper;
import mensageria.models.ItemMenu;
import mensageria.models.Pedido;
import mensageria.models.PedidoItem;
import mensageria.models.CardapioResponse;
import com.sun.net.httpserver.HttpServer;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.util.*;
import java.util.UUID;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.stream.Collectors;

public class ServidorRestaurante {

    private static final int PORTA_SERVIDOR = 8080;
    private static final String API_CARDAPIO_URL = "http://localhost:1880/cardapioRest";

    private static final Map<Integer, ItemMenu> cardapio = new HashMap<>();
    private static final AtomicInteger proximoIdPedido = new AtomicInteger(1);
    private static final ObjectMapper mapper = new ObjectMapper();
    private static final String NOME_SERVICO = "servidor do restaurante";
    private static final HttpClient httpClient = HttpClient.newBuilder()
            .version(HttpClient.Version.HTTP_1_1)
            .build();

    public static void main(String[] args) {

        // antes de qualquer coisa, o restaurante se registra no name server
        try {
            System.out.println("Registrando '" + NOME_SERVICO + "' no Servidor de Nomes...");
            Servico meuServico = new Servico(NOME_SERVICO, "localhost", 8080);
            NameServiceClient.registrarServico(meuServico);
            System.out.println("Serviço registrado com sucesso!");
        } catch (IOException e) {
            System.err.println("não conseguiu registrar no servidor de nomes, provavelmente ele não ta rodando");
            return;
        }

        // se não conseguir puxar o cardápio da api, não faz sentido continuar
        if (!carregarCardapioDaAPI()) {
            System.err.println("Erro fatal: Não foi possível carregar o cardápio. Encerrando.");
            return;
        }

        try {
            // sobe o servidor http na porta definida
            HttpServer server = HttpServer.create(new InetSocketAddress(PORTA_SERVIDOR), 0);
            System.out.println("Servidor aguardando requisições HTTP na porta " + PORTA_SERVIDOR + "...");
            System.out.println("--- Restaurante Operacional ---");

            // endpoint do cardápio (GET)
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
                    exchange.sendResponseHeaders(405, -1); // método não permitido
                }
            });

            // endpoint pra criar pedido (POST)
            server.createContext("/pedido", exchange -> {
                if ("POST".equals(exchange.getRequestMethod())) {
                    // pega o corpo da requisição (pedido em texto)
                    String pedidoStr = new BufferedReader(
                            new InputStreamReader(exchange.getRequestBody(), StandardCharsets.UTF_8))
                            .lines()
                            .collect(Collectors.joining("\n"));

                    System.out.println("\nPedido HTTP recebido: " + pedidoStr);

                    // confirma e processa o pedido
                    String resposta = confirmarPedido(
                            pedidoStr,
                            exchange.getRemoteAddress().getAddress().getHostAddress(),
                            exchange.getRemoteAddress().getPort()
                    );

                    // manda a resposta de volta pro cliente
                    byte[] respostaBytes = resposta.getBytes(StandardCharsets.UTF_8);
                    exchange.sendResponseHeaders(200, respostaBytes.length);

                    try (OutputStream os = exchange.getResponseBody()) {
                        os.write(respostaBytes);
                    }

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

    // carrega cardápio da api do node-red
    private static boolean carregarCardapioDaAPI() {
        System.out.println("Carregando cardápio da API...");
        try {
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(API_CARDAPIO_URL))
                    .header("Accept", "application/json")
                    .GET()
                    .build();

            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

            if (response.statusCode() == 200) {
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

    // pega lista de pedidos já existentes na api
    private static List<Pedido> carregarPedidosDaAPI() {
        try {
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create("http://127.0.0.1:1880/pedidos"))
                    .header("Accept", "application/json")
                    .GET()
                    .build();

            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

            if (response.statusCode() == 200) {
                return mapper.readValue(response.body(), mapper.getTypeFactory().constructCollectionType(List.class, Pedido.class));
            } else {
                System.err.println("Falha ao carregar pedidos. Status: " + response.statusCode());
            }
        } catch (Exception e) {
            System.err.println("Erro ao carregar pedidos da API: " + e.getMessage());
        }
        return new ArrayList<>();
    }

    // valida e confirma o pedido, desconta do estoque e manda pra api
    private static String confirmarPedido(String pedidoStr, String clienteHost, int clientePorta) {
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
                if (itemCardapio.getQuantidade() < quantidade) {
                    return "Estoque insuficiente para o item " + itemCardapio.getNome() +
                            ". Disponível: " + itemCardapio.getQuantidade();
                }

                itensPedidos.add(new PedidoItem(id, itemCardapio.getNome(), quantidade, itemCardapio.getPreco()));
                valorTotal += itemCardapio.getPreco() * quantidade;
            }

            // desconta os itens do estoque
            for (PedidoItem pi : itensPedidos) {
                ItemMenu item = cardapio.get(pi.getId());
                item.setQuantidade(item.getQuantidade() - pi.getQuantidade());
            }

            // cria o pedido com id único
            Pedido novoPedido = new Pedido(itensPedidos, clienteHost, clientePorta, "Confirmado");
            List<Pedido> pedidosExistentes = carregarPedidosDaAPI();
            int maxId = pedidosExistentes.stream()
                    .mapToInt(Pedido::getIdPedido)
                    .max()
                    .orElse(0);
            int idPedido = maxId + 1;
            novoPedido.setIdPedido(idPedido);
            novoPedido.setValorTotal(valorTotal);

            // manda o pedido pra api
            String json = mapper.writeValueAsString(novoPedido);
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create("http://127.0.0.1:1880/pedidos"))
                    .header("Content-Type", "application/json")
                    .POST(HttpRequest.BodyPublishers.ofString(json))
                    .build();

            HttpResponse<String> apiResponse = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

            if (apiResponse.statusCode() == 201 || apiResponse.statusCode() == 200) {
                System.out.println("Pedido ID " + novoPedido.getIdPedido() + " enviado com sucesso para a API!");
            } else {
                return "Erro ao registrar o pedido na API. Status: " + apiResponse.statusCode();
            }

            // monta mensagem de confirmação pro cliente
            StringBuilder confirmacao = new StringBuilder();
            confirmacao.append("Pedido confirmado!\n");
            confirmacao.append("Itens:\n");
            for (PedidoItem pi : itensPedidos) {
                confirmacao.append(" - ").append(pi.getNomeItem())
                        .append(" x").append(pi.getQuantidade())
                        .append(" (R$ ").append(String.format("%.2f", pi.getPrecoUnitario())).append(")\n");
            }
            confirmacao.append("Valor Total: R$ ").append(String.format("%.2f", valorTotal)).append("\n");

            return confirmacao.toString();

        } catch (Exception e) {
            e.printStackTrace();
            return "Formato do pedido inválido. Use ID-QUANTIDADE.";
        }
    }
}
