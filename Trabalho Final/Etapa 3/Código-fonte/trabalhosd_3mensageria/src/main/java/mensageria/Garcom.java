package mensageria;

import mensageria.models.Pedido;
import com.fasterxml.jackson.databind.ObjectMapper;

import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.util.List;
import java.util.Set;
import java.util.concurrent.ConcurrentHashMap;

public class Garcom {

    // url da api de pedidos
    private static final String API_PEDIDOS = "http://127.0.0.1:1880/pedidos";
    // pra converter objeto ↔ json
    private static final ObjectMapper mapper = new ObjectMapper();
    // cliente http usado nas requisições
    private static final HttpClient httpClient = HttpClient.newBuilder().version(HttpClient.Version.HTTP_1_1).build();
    // guardo os ids dos pedidos que já foram processados pra não repetir
    private static final Set<Integer> pedidosProcessados = ConcurrentHashMap.newKeySet();

    // só pra organizar os status que vou usar
    private static class Status {
        static final String PRONTO = "Pronto";
        static final String ENTREGUE = "Entregue";
    }

    public static void main(String[] args) {
        System.out.println("Garçom iniciado. Observando pedidos 'Pronto' na API...");

        while (true) {
            try {
                // pega todos os pedidos que tão na api
                List<Pedido> pedidos = carregarPedidosDaAPI();
                for (Pedido p : pedidos) {
                    // se o pedido estiver pronto e ainda não foi processado
                    if (Status.PRONTO.equalsIgnoreCase(p.getStatus()) && !pedidosProcessados.contains(p.getIdPedido())) {
                        System.out.println("[Garçom] PEDIDO " + p.getIdPedido() + " Pronto para ser entregue");
                        // muda o status pra entregue
                        atualizarStatusNaApi(p, Status.ENTREGUE);
                        // adiciona na lista de já processados
                        pedidosProcessados.add(p.getIdPedido());
                        // só pra mostrar no console que entregou
                        System.out.println("[Garçom] PEDIDO " + p.getIdPedido() + " entregue para CLIENTE " + p.getPortaCliente());
                    }
                }
                // espera 3s antes de checar de novo
                Thread.sleep(3000);
            } catch (InterruptedException ie) {
                // se o programa for interrompido, sai do loop
                Thread.currentThread().interrupt();
                break;
            } catch (Exception e) {
                // se der qualquer erro no loop
                System.err.println("[Garçom] Erro no loop de observação: " + e.getMessage());
            }
        }
    }

    private static List<Pedido> carregarPedidosDaAPI() {
        try {
            // monta o GET na api de pedidos
            HttpRequest req = HttpRequest.newBuilder()
                    .uri(java.net.URI.create(API_PEDIDOS))
                    .header("Accept", "application/json")
                    .GET()
                    .build();

            HttpResponse<String> resp = httpClient.send(req, HttpResponse.BodyHandlers.ofString());
            if (resp.statusCode() == 200) {
                // converte a resposta em lista de pedidos
                return mapper.readValue(resp.body(),
                        mapper.getTypeFactory().constructCollectionType(List.class, Pedido.class));
            } else {
                System.err.println("[Garçom] Erro ao carregar pedidos. HTTP " + resp.statusCode());
            }
        } catch (Exception e) {
            System.err.println("[Garçom] Erro no GET /pedidos: " + e.getMessage());
        }
        // se deu erro, devolvo lista vazia pra não quebrar
        return List.of();
    }

    private static void atualizarStatusNaApi(Pedido pedido, String novoStatus) {
        try {
            // troca o status no objeto
            pedido.setStatus(novoStatus);

            // transforma o pedido em json
            String json = mapper.writeValueAsString(pedido);
            // monta o PUT pra atualizar na api
            HttpRequest putReq = HttpRequest.newBuilder()
                    .uri(java.net.URI.create(API_PEDIDOS + "/" + pedido.getIdPedido()))
                    .header("Content-Type", "application/json")
                    .PUT(HttpRequest.BodyPublishers.ofString(json))
                    .build();

            HttpResponse<String> putResp = httpClient.send(putReq, HttpResponse.BodyHandlers.ofString());
            if (putResp.statusCode() != 200) {
                System.err.println("[Garçom] Falha ao atualizar status do pedido " + pedido.getIdPedido()
                        + " para " + novoStatus + ". HTTP " + putResp.statusCode());
            }
        } catch (Exception e) {
            System.err.println("[Garçom] Erro ao atualizar status na API: " + e.getMessage());
        }
    }
}
