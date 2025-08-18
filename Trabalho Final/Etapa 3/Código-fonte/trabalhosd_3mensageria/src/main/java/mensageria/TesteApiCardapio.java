package mensageria;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;

public class TesteApiCardapio {
    public static void main(String[] args) {
        // endereço da api que vou testar
        String API_CARDAPIO_URL = "http://127.0.0.1:1880/cardapioRest";

        System.out.println("Tentando conectar à API em: " + API_CARDAPIO_URL);

        // crio o cliente http (usei http/1.1 só pra garantir compatibilidade)
        HttpClient httpClient = HttpClient.newBuilder()
                .version(HttpClient.Version.HTTP_1_1)
                .build();

        // preparo a requisição GET pro endpoint do cardápio
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create("http://127.0.0.1:1880/cardapioRest")) // coloquei ipv4 direto
                .header("Accept", "application/json") // espero que a resposta venha em json
                .GET()
                .build();

        try {
            // mando a requisição e espero resposta em string
            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

            // mostro o status http que voltou
            System.out.println("Status da Resposta: " + response.statusCode());
            // mostro o corpo da resposta (json que a api devolver)
            System.out.println("Corpo da Resposta:\n" + response.body());

        } catch (Exception e) {
            // se der erro na conexão ou algo assim, aviso no console
            System.err.println("Erro ao conectar à API: " + e.getMessage());
            e.printStackTrace();
        }
    }
}
