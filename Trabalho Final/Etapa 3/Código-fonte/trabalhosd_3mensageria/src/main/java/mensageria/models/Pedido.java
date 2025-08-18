package mensageria.models;

import java.io.Serializable;
import java.util.List;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public class Pedido implements Serializable {
    //o json irá usar o campo id para subir os pedidos pra api
    @JsonProperty("id")
    private int id;
    private List<PedidoItem> itens;
    private double valorTotal;
    private String enderecoCliente;
    private int portaCliente;
    private String status;

    public Pedido() {}

    //construtor padrao
    public Pedido(List<PedidoItem> itens, String enderecoCliente, int portaCliente, String status) {
        this.itens = itens;
        this.enderecoCliente = enderecoCliente;
        this.portaCliente = portaCliente;
        this.status = status; // valor padrão
    }

    // Getters e setters
    public int getIdPedido() { return id; }
    public void setIdPedido(int idPedido) { this.id = idPedido; }

    public List<PedidoItem> getItens() { return itens; }
    public void setItens(List<PedidoItem> itens) { this.itens = itens; }

    public double getValorTotal() { return valorTotal; }
    public void setValorTotal(double valorTotal) { this.valorTotal = valorTotal; }

    public String getEnderecoCliente() { return enderecoCliente; }
    public void setEnderecoCliente(String enderecoCliente) { this.enderecoCliente = enderecoCliente; }

    public int getPortaCliente() { return portaCliente; }
    public void setPortaCliente(int portaCliente) { this.portaCliente = portaCliente; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }
}
