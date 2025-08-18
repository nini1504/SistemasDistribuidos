package mensageria.models;

import java.io.Serializable;

public class PedidoItem implements Serializable {
    private int id;
    private int quantidade;
    private String nomeItem;
    private double precoUnitario;

    public PedidoItem() {}

    // construtor básico
    public PedidoItem(int id, int quantidade) {
        this.id = id;
        this.quantidade = quantidade;
    }

    // construtor completo
    public PedidoItem(int id, String nomeItem, int quantidade, double precoUnitario) {
        this.id = id;
        this.nomeItem = nomeItem;
        this.quantidade = quantidade;
        this.precoUnitario = precoUnitario;
    }

    //getters e setters
    public int getQuantidade() { return quantidade; }
    public void setQuantidade(int quantidade) { this.quantidade = quantidade; }

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public String getNomeItem() { return nomeItem; }
    public void setNomeItem(String nomeItem) { this.nomeItem = nomeItem; }

    public double getPrecoUnitario() { return precoUnitario; }
    public void setPrecoUnitario(double precoUnitario) { this.precoUnitario = precoUnitario; }

    @Override
    public String toString() {
        return nomeItem + " x" + quantidade + " (R$ " + String.format("%.2f", precoUnitario) + ")";
    }
}
