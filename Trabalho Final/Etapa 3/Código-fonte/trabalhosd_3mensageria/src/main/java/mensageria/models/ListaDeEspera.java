package mensageria.models;
import java.util.LinkedList;



public class ListaDeEspera {

    private final LinkedList<Pedido> listaPedidos;



    public ListaDeEspera() {

        this.listaPedidos = new LinkedList<>();

    }



    public synchronized void adicionarPedido(Pedido pedido) {

        listaPedidos.addLast(pedido);

    }



    public synchronized Pedido removerProximoPedido() {

        if (listaPedidos.isEmpty()) {

            return null;

        }

        return listaPedidos.removeFirst();

    }



    public synchronized boolean estaVazia() {

        return listaPedidos.isEmpty();

    }

}