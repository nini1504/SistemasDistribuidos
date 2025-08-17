package mensageria;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;

public class TesteApiCardapio {
    public static void main(String[] args) {
        String API_CARDAPIO_URL = "http://127.0.0.1:1880/cardapioRest";

        System.out.println("Tentando conectar à API em: " + API_CARDAPIO_URL);

        HttpClient httpClient = HttpClient.newBuilder()
                .version(HttpClient.Version.HTTP_1_1) // força HTTP/1.1
                .build();

        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create("http://127.0.0.1:1880/cardapioRest")) // usa IPv4
                .header("Accept", "application/json")
                .GET()
                .build();

        try {
            HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());

            System.out.println("Status da Resposta: " + response.statusCode());
            System.out.println("Corpo da Resposta:\n" + response.body());

        } catch (Exception e) {
            System.err.println("Erro ao conectar à API: " + e.getMessage());
            e.printStackTrace();
        }
    }
}