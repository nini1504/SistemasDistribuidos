package mensageria.models;

import java.util.List;

/*É uma classe de modelo que basicamente organiza o retorno do cardápio e facilita
quando a gente usa o ObjectMapper do Jackson pra transformar JSON em objeto Java.*/

public class CardapioResponse {
    // essa lista guarda os itens do cardápio (pode ser prato, bebida, etc)
    private List<ItemMenu> itens;

    // retorna a lista de itens quando eu precisar acessar
    public List<ItemMenu> getItens() {
        return itens;
    }

    // permite alterar/definir a lista de itens
    public void setItens(List<ItemMenu> itens) {
        this.itens = itens;
    }
}
