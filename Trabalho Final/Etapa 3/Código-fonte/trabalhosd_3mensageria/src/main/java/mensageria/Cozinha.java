package mensageria;

import mensageria.models.Pedido;
import com.fasterxml.jackson.databind.ObjectMapper;

import java.net.http.*;
import java.net.URI;
import java.util.List;
import java.util.Set;
import java.util.concurrent.*;

public class Cozinha {

    private static final String API_PEDIDOS = "http://127.0.0.1:1880/pedidos"; // url da api que gerencia pedidos
    private final BlockingQueue<Pedido> filaPedidos = new LinkedBlockingQueue<>(); // fila dos pedidos que a cozinha vai preparar
    private final ExecutorService cozinhaPool = Executors.newFixedThreadPool(3); // consigo preparar até 3 pedidos ao mesmo tempo
    private static final HttpClient httpClient = HttpClient.newBuilder()
            .version(HttpClient.Version.HTTP_1_1) // vou usar http 1.1
            .build();
    private final ObjectMapper mapper = new ObjectMapper(); // pra lidar com json
    private final Set<Integer> pedidosEmPreparo = ConcurrentHashMap.newKeySet(); // guardo aqui os ids dos pedidos que já estão sendo feitos
    private final int CAPACIDADE = 3; // limite da cozinha

    // defini os status do pedido pra não ficar repetindo string no código
    private static class Status {
        static final String CONFIRMADO = "Confirmado";
        static final String PREPARANDO = "Preparando";
        static final String PRONTO = "Pronto";
    }

    public static void main(String[] args) {
        Cozinha cozinha = new Cozinha();
        cozinha.iniciar(); // começo a "rodar" a cozinha
    }

    public void iniciar() {
        System.out.println("Cozinha iniciada. Aguardando pedidos...");

        // thread que fica consultando a API e pegando novos pedidos
        new Thread(() -> {
            while (true) {
                try {
                    if (pedidosEmPreparo.size() < CAPACIDADE) {
                        List<Pedido> pedidos = carregarPedidosDaAPI(); // puxo os pedidos
                        for (Pedido p : pedidos) {
                            if (pedidosEmPreparo.size() >= CAPACIDADE) break; // se já tiver no limite, para
                            if (!pedidosEmPreparo.contains(p.getIdPedido())
                                    && Status.CONFIRMADO.equalsIgnoreCase(p.getStatus())) {
                                // só adiciono pedidos que estão confirmados e ainda não estão em preparo
                                filaPedidos.offer(p);
                                pedidosEmPreparo.add(p.getIdPedido());
                                System.out.println("[Fila] Pedido ID " + p.getIdPedido() + " adicionado à fila");
                            }
                        }
                    }
                    Thread.sleep(5000); // espera 5 segundos e consulta de novo
                } catch (Exception e) {
                    e.printStackTrace();
                }
            }
        }).start();

        // thread que pega da fila e manda preparar (até 3 ao mesmo tempo por causa do pool)
        new Thread(() -> {
            while (true) {
                try {
                    Pedido pedido = filaPedidos.take(); // bloqueia até ter algo na fila
                    cozinhaPool.submit(() -> processarPedido(pedido)); // joga o pedido numa thread do pool
                } catch (InterruptedException e) {
                    e.printStackTrace();
                }
            }
        }).start();
    }

    private void processarPedido(Pedido pedido) {
        final int id = pedido.getIdPedido();
        try {
            // primeiro muda status pra PREPARANDO
            atualizarStatus(pedido, Status.PREPARANDO);
            System.out.println("[Preparando] Pedido ID " + id + " começou a ser preparado...");

            // simulação do tempo de preparo (aqui tá 1 min só pra testar, mas a ideia seria mais tempo)
            Thread.sleep(1 * 60 * 1000);

            // quando termina, muda pra PRONTO
            atualizarStatus(pedido, Status.PRONTO);
            System.out.println("[Pronto] Pedido ID " + id + " está pronto!");

            // aqui seria o ponto de chamar o garçom pra entregar
            //Garcom.entregarPedido(pedido);

        } catch (Exception e) {
            System.err.println("Erro ao processar pedido " + id + ": " + e.getMessage());
        } finally {
            pedidosEmPreparo.remove(id); // tira da lista de em preparo, liberando espaço
        }
    }

    private void atualizarStatus(Pedido pedido, String status) {
        try {
            pedido.setStatus(status);

            String json = mapper.writeValueAsString(pedido); // converte o pedido atualizado em json
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(API_PEDIDOS + "/" + pedido.getIdPedido()))
                    .header("Content-Type", "application/json")
                    .PUT(HttpRequest.BodyPublishers.ofString(json)) // manda o update
                    .build();

            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

            if (response.statusCode() != 200) {
                System.err.println("Falha ao atualizar status do pedido " + pedido.getIdPedido()
                        + " para " + status + ". HTTP " + response.statusCode());
            }

        } catch (Exception e) {
            System.err.println("Erro ao atualizar pedido " + pedido.getIdPedido() + ": " + e.getMessage());
        }
    }

    private List<Pedido> carregarPedidosDaAPI() {
        try {
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(API_PEDIDOS))
                    .header("Accept", "application/json")
                    .GET()
                    .build();

            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

            if (response.statusCode() == 200) {
                return mapper.readValue(response.body(),
                        mapper.getTypeFactory().constructCollectionType(List.class, Pedido.class));
            } else {
                System.err.println("Erro ao carregar pedidos. Status: " + response.statusCode());
            }
        } catch (Exception e) {
            System.err.println("Erro ao carregar pedidos da API: " + e.getMessage());
        }
        return List.of(); // se der erro, devolvo lista vazia pra não quebrar
    }
}
