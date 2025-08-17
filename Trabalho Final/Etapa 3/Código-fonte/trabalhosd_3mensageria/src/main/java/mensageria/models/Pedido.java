package mensageria.models;

import java.io.Serializable;
import java.util.List;
import java.util.stream.Collectors;

public class Pedido implements Serializable {
    public int idPedido;
    public String idCliente;
    public List<PedidoItem> itens;
    public double valorTotal;
    public String clienteHost;
    public int clientePorta;

    public Pedido() {}

    public Pedido(String idCliente, List<PedidoItem> itens, String clienteHost, int clientePorta) {
        this.idCliente = idCliente;
        this.itens = itens;
        this.clienteHost = clienteHost;
        this.clientePorta = clientePorta;
    }

    // Getters e Setters
    public int getIdPedido() { return idPedido; }
    public void setIdPedido(int idPedido) { this.idPedido = idPedido; }
    public String getIdCliente() { return idCliente; }
    public void setIdCliente(String idCliente) { this.idCliente = idCliente; }
    public List<PedidoItem> getItens() { return itens; }
    public void setItens(List<PedidoItem> itens) { this.itens = itens; }
    public double getValorTotal() { return valorTotal; }
    public void setValorTotal(double valorTotal) { this.valorTotal = valorTotal; }
    public String getClienteHost() { return clienteHost; }
    public void setClienteHost(String clienteHost) { this.clienteHost = clienteHost; }
    public int getClientePorta() { return clientePorta; }
    public void setClientePorta(int clientePorta) { this.clientePorta = clientePorta; }

    @Override
    public String toString() {
        String itensStr = itens.stream()
                .map(item -> "ID " + item.getId() + ", Qtd: " + item.getQuantidade())
                .collect(Collectors.joining("; "));

        // Código ajustado para incluir as informações do cliente na saída
        return String.format("Pedido ID: %d | Cliente: %s | Host: %s | Porta: %d | Valor Total: R$%.2f | Itens: [%s]",
                idPedido, idCliente, clienteHost, clientePorta, valorTotal, itensStr);
    }
}