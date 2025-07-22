import java.io.Serializable;
import java.util.concurrent.atomic.AtomicInteger;

class ItemMenu implements Serializable {
    private String tipo;
    private int id;
    private String nome;
    private double preco;
    private String descricao;
    private int tempoDePreparoMinutos;
    private AtomicInteger estoque;

    public ItemMenu(String tipo, int id, String nome, double preco, String descricao, int tempoDePreparoMinutos, int estoqueInicial) {
        this.tipo = tipo;
        this.id = id;
        this.nome = nome;
        this.preco = preco;
        this.descricao = descricao;
        this.tempoDePreparoMinutos = tempoDePreparoMinutos;
        this.estoque = new AtomicInteger(estoqueInicial);
    }

    public String getTipo() {
        return tipo;
    }

    public int getId() {
        return id;
    }

    public String getNome() {
        return nome;
    }

    public double getPreco() {
        return preco;
    }

    public String getDescricao() {
        return descricao;
    }

    public int getTempoDePreparoMinutos() {
        return tempoDePreparoMinutos;
    }

    public int getEstoque() {
        return estoque.get();
    }

    public boolean diminuirEstoque(int quantidade) {
        int estoqueAtual;
        int novoEstoque;
        do {
            estoqueAtual = this.estoque.get();
            novoEstoque = estoqueAtual - quantidade;
            if (novoEstoque < 0) {
                return false;
            }
        } while (!this.estoque.compareAndSet(estoqueAtual, novoEstoque));
        return true;
    }

    public void adicionarEstoque(int quantidade) {
        this.estoque.addAndGet(quantidade);
    }

    @Override
    public String toString() {
        return "ID: " + id + " | " + nome + " (" + tipo + ") - R$" + String.format("%.2f", preco) + " | Tempo: " + tempoDePreparoMinutos + " min";
    }
}