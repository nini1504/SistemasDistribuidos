import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.PrintWriter;
import java.net.ServerSocket;
import java.net.Socket;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.atomic.AtomicInteger;

class ServidorRestaurante {
    private static final List<ItemMenu> menu = new ArrayList<>();
    private static final Object PEDIDO_LOCK = new Object();


    static {
        menu.add(new ItemMenu("Prato", 1, "Pizza Calabresa", 45.00, "Massa fina, molho de tomate, calabresa, cebola e mussarela.", 20, 10));
        menu.add(new ItemMenu("Bebida", 2, "Coca-Cola 350ml", 7.50, "Refrigerante de cola em lata.", 2, 10));
        menu.add(new ItemMenu("Prato", 3, "Lasanha à Bolonhesa", 38.00, "Camadas de massa, molho bolonhesa, presunto, queijo e molho branco.", 30, 10));
        menu.add(new ItemMenu("Bebida", 4, "Suco de Laranja Natural", 12.00, "Suco fresco de laranjas selecionadas.", 5, 10));
        menu.add(new ItemMenu("Prato", 5, "Salmão Grelhado", 65.00, "Filé de salmão grelhado com legumes salteados.", 25, 10));
    }

    public static void main(String[] args) {
        try (ServerSocket server = new ServerSocket(8080)) {
            server.setReuseAddress(true);
            System.out.println("Servidor aguardando conexões na porta 8080...");
            System.out.println("\n--- Restaurante Operacional ---");

            while (true) {
                Socket client = server.accept();
                System.out.println("\nNovo cliente conectado - Porta: " + client.getPort());
                System.out.println("Enviando menu para o cliente " + client.getPort() + "...");

                new Thread(new ClientHandler(client)).start();
            }
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private static class ClientHandler implements Runnable {
        private final Socket clientSocket;

        public ClientHandler(Socket socket) {
            this.clientSocket = socket;
        }

        public void run() {
            try (PrintWriter out = new PrintWriter(clientSocket.getOutputStream(), true);
                 BufferedReader in = new BufferedReader(
                         new InputStreamReader(clientSocket.getInputStream()))) {

                // 1. Servidor exibe o menu para o cliente (sem exibir estoque)
                StringBuilder menuStr = new StringBuilder();
                menuStr.append("--- Menu do Restaurante ---\n");
                for (ItemMenu item : menu) {
                    menuStr.append(item.toString()).append("\n");
                }
                menuStr.append("---------------------------\n");
                menuStr.append("Por favor, digite seu pedido no formato: ID_ITEM1-QUANTIDADE1,ID_ITEM2-QUANTIDADE2 (ex: 1-2,3-1)");
                out.println(menuStr.toString());

                String clientMessage;
                while ((clientMessage = in.readLine()) != null) {
                    if (clientMessage.equalsIgnoreCase("exit")) {
                        System.out.println("Cliente " + clientSocket.getPort() + " encerrou a sessão.");
                        break;
                    }

                    System.out.println("\nPedido recebido do cliente " + clientSocket.getPort() + ": " + clientMessage);

                    String[] itensPedidos = clientMessage.split(",");
                    Map<ItemMenu, Integer> pedidoCliente = new HashMap<>();
                    boolean pedidoValido = true;
                    StringBuilder respostaAoCliente = new StringBuilder();
                    double valorTotal = 0.0;

                    for (String itemStr : itensPedidos) {
                        try {
                            String[] partes = itemStr.split("-");
                            if (partes.length == 2) {
                                int itemId = Integer.parseInt(partes[0].trim());
                                int quantidade = Integer.parseInt(partes[1].trim());

                                if (quantidade <= 0) {
                                    respostaAoCliente.append("\nQuantidade inválida (menor ou igual a zero) para o item ID ").append(itemId).append(".\n");
                                    pedidoValido = false;
                                    break;
                                }

                                ItemMenu item = menu.stream()
                                        .filter(i -> i.getId() == itemId)
                                        .findFirst()
                                        .orElse(null);

                                if (item == null) {
                                    respostaAoCliente.append("Item com ID ").append(itemId).append(" não encontrado no menu.\n");
                                    pedidoValido = false;
                                    break;
                                }

                                pedidoCliente.put(item, quantidade);
                            } else {
                                respostaAoCliente.append("Formato de pedido inválido: '").append(itemStr).append("'. Use ID-QUANTIDADE.\n");
                                pedidoValido = false;
                                break;
                            }
                        } catch (NumberFormatException e) {
                            respostaAoCliente.append("Formato de ID ou quantidade inválido em '").append(itemStr).append("'.\n");
                            pedidoValido = false;
                            break;
                        }
                    }

                    if (pedidoValido) {
                        synchronized (PEDIDO_LOCK) {
                            boolean podeProcessar = true;
                            for (Map.Entry<ItemMenu, Integer> entry : pedidoCliente.entrySet()) {
                                ItemMenu item = entry.getKey();
                                int quantidadePedida = entry.getValue();
                                int quantidadeDisponivel = item.getEstoque();

                                if (quantidadePedida > quantidadeDisponivel) {
                                    respostaAoCliente.append("Desculpe, não temos ").append(quantidadePedida)
                                            .append(" unidades do item '").append(item.getNome())
                                            .append("'. Disponível: ").append(quantidadeDisponivel).append(".\n");
                                    podeProcessar = false;
                                    break;
                                }
                            }

                            if (podeProcessar) {
                                respostaAoCliente.append("Pedido Confirmado para cliente ").append(clientSocket.getPort()).append(":\n");
                                for (Map.Entry<ItemMenu, Integer> entry : pedidoCliente.entrySet()) {
                                    ItemMenu item = entry.getKey();
                                    int quantidadePedida = entry.getValue();
                                    item.diminuirEstoque(quantidadePedida);
                                    valorTotal += item.getPreco() * quantidadePedida;
                                    respostaAoCliente.append("- ").append(quantidadePedida).append("x ")
                                            .append(item.getNome()).append(" (R$").append(String.format("%.2f", item.getPreco() * quantidadePedida)).append(")\n");
                                }
                                respostaAoCliente.append("\nValor Total: R$").append(String.format("%.2f", valorTotal)).append("\n");
                                respostaAoCliente.append("\nSeu pedido será preparado em breve!");

                                System.out.println("Estoque atualizado após pedido do cliente " + clientSocket.getPort() + ":");
                                for (ItemMenu item : menu) {
                                    System.out.println(item.getNome() + " (ID: " + item.getId() + ") - Estoque: " + item.getEstoque());
                                }
                            } else {
                                respostaAoCliente.insert(0, "Pedido RECUSADO para cliente " + clientSocket.getPort() + ":\n");
                            }
                        }
                    } else {
                        respostaAoCliente.insert(0, "Pedido RECUSADO para cliente " + clientSocket.getPort() + ":\n");
                    }

                    System.out.println("Enviando resposta para cliente " + clientSocket.getPort() + ":\n" + respostaAoCliente.toString());

                    // <<<<<<<<<<<<< MUDANÇA CRÍTICA AQUI >>>>>>>>>>>>>>>
                    // Envia cada linha do StringBuilder separadamente
                    String[] linhasResposta = respostaAoCliente.toString().split("\n");
                    for (String linha : linhasResposta) {
                        out.println(linha);
                    }
                    // <<<<<<<<<<<<< FIM DA MUDANÇA CRÍTICA >>>>>>>>>>>>>>

                    out.println("FIM_DA_RESPOSTA"); // Envia o marcador final após todas as linhas
                    System.out.println("Resposta e marcador FIM_DA_RESPOSTA enviados para cliente " + clientSocket.getPort() + ".");
                }
            } catch (IOException e) {
                System.out.println("Erro de IO na conexão com o cliente " + clientSocket.getPort() + ": " + e.getMessage());
                e.printStackTrace();
            } catch (Exception e) {
                System.out.println("Erro inesperado no ClientHandler para cliente " + clientSocket.getPort() + ": " + e.getMessage());
                e.printStackTrace();
            }
            finally {
                try {
                    if (clientSocket != null && !clientSocket.isClosed()) {
                        System.out.println("Fechando conexão com o cliente " + clientSocket.getPort() + ".");
                        clientSocket.close();
                    }
                } catch (IOException e) {
                    System.out.println("Erro ao fechar o socket do cliente " + clientSocket.getPort() + ": " + e.getMessage());
                    e.printStackTrace();
                }
            }
        }
    }
}