package mensageria.models;

import java.io.Serializable;

public class PedidoItem implements Serializable {
    public int id;
    public int quantidade;

    public PedidoItem() {}
    public PedidoItem(int id, int quantidade) {
        this.id = id;
        this.quantidade = quantidade;
    }
    public int getQuantidade() {
        return quantidade;
    }

    public void setQuantidade(int quantidade) {
        this.quantidade = quantidade;
    }

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }
}