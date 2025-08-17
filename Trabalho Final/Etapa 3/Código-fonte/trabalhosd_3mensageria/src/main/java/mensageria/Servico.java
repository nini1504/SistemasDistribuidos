package mensageria;

import com.fasterxml.jackson.annotation.JsonProperty;

// A anotação @JsonProperty é útil se os nomes das propriedades no JSON
// forem diferentes dos nomes das variáveis na classe Java.
// Para este exemplo, não é estritamente necessário, mas é uma boa prática.
public class Servico {

    private String nome;
    private String host;
    private int porta;

    // Construtor padrão (sem argumentos) é necessário para a desserialização do Jackson
    public Servico() {
    }

    public Servico(String nome, String host, int porta) {
        this.nome = nome;
        this.host = host;
        this.porta = porta;
    }

    // Getters para acessar as propriedades
    public String getNome() {
        return nome;
    }

    public String getHost() {
        return host;
    }

    public int getPorta() {
        return porta;
    }

    // Setters para definir as propriedades
    // O Jackson usa esses métodos para "encher" o objeto a partir do JSON
    public void setNome(String nome) {
        this.nome = nome;
    }

    public void setHost(String host) {
        this.host = host;
    }

    public void setPorta(int porta) {
        this.porta = porta;
    }

    @Override
    public String toString() {
        return "Servico{" +
                "nome='" + nome + '\'' +
                ", host='" + host + '\'' +
                ", porta=" + porta +
                '}';
    }
}