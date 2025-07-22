import java.io.*;
import java.net.*;
import java.util.Scanner;

class ClienteRestaurante {
    public static void main(String[] args) {
        try (Socket socket = new Socket("localhost", 8080);
             PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
             BufferedReader in = new BufferedReader(
                     new InputStreamReader(socket.getInputStream()));
             Scanner sc = new Scanner(System.in)) {

            System.out.println("Conectado ao servidor.");

            System.out.println("\n--- Menu Recebido do Servidor ---");
            String linhaMenu;
            while ((linhaMenu = in.readLine()) != null && !linhaMenu.startsWith("Por favor, digite seu pedido")) {
                System.out.println(linhaMenu);
            }
            if (linhaMenu != null) {
                System.out.println(linhaMenu);
            }

            String userInput;
            while (true) {
                System.out.print("\nSeu pedido (ID-QUANTIDADE,ID-QUANTIDADE ou 'exit' para sair): ");
                userInput = sc.nextLine();

                if ("exit".equalsIgnoreCase(userInput)) {
                    out.println("exit");
                    break;
                }

                out.println(userInput);

                // <<<<<<<<<<<<< ADICIONADO AQUI PARA DEBUG >>>>>>>>>>>>>>>
                try {
                    Thread.sleep(100); // Dá um pequeno tempo para o servidor processar e enviar
                } catch (InterruptedException e) {
                    Thread.currentThread().interrupt();
                }
                // <<<<<<<<<<<<< FIM DA ADIÇÃO >>>>>>>>>>>>>>>

                System.out.println("\n--- Resposta do Servidor ---");
                String respostaServidor;
                while ((respostaServidor = in.readLine()) != null) {
                    if (respostaServidor.equals("FIM_DA_RESPOSTA")) {
                        break;
                    }
                    System.out.println(respostaServidor);
                }
                System.out.println("---------------------------");

            }
        } catch (IOException e) {
            System.err.println("Erro na conexão ou comunicação: " + e.getMessage());
        }
        System.out.println("Conexão encerrada.");
    }
}