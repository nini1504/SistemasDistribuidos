package mensageria.models;

import com.fasterxml.jackson.annotation.JsonProperty;
import java.io.Serializable;

public class ItemMenu implements Serializable {
    public int id;
    public String tipo;
    public String nome;
    public double preco;
    public String descricao;
    @JsonProperty("tempo_preparo")
    public String tempo_preparo;
    public int quantidade;

    public ItemMenu() {}

    @Override
    public String toString() {
        return "ID: " + id + " | " + nome + " (" + tipo + ") - R$" + String.format("%.2f", preco) + " | Tempo: " + tempo_preparo;
    }

    // Getters e Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getTipo() { return tipo; }
    public void setTipo(String tipo) { this.tipo = tipo; }
    public String getNome() { return nome; }
    public void setNome(String nome) { this.nome = nome; }
    public double getPreco() { return preco; }
    public void setPreco(double preco) { this.preco = preco; }
    public String getDescricao() { return descricao; }
    public void setDescricao(String descricao) { this.descricao = descricao; }
    public String getTempo_preparo() { return tempo_preparo; }
    public void setTempo_preparo(String tempo_preparo) { this.tempo_preparo = tempo_preparo; }
    public int getQuantidade() { return quantidade; }
    public void setQuantidade(int quantidade) { this.quantidade = quantidade; }
}