import java.util.ArrayList;
import java.util.List;

public class Main {
    public static void main(String[] args) {
        List<ItemMenu> menu = new ArrayList<>();

        menu.add(new ItemMenu("Prato", 1, "Pizza Calabresa", 45.00, "Massa fina, molho de tomate, calabresa, cebola e mussarela.", 20, 10));
        menu.add(new ItemMenu("Bebida", 2, "Coca-Cola 350ml", 7.50, "Refrigerante de cola em lata.", 2, 10));
        menu.add(new ItemMenu("Prato", 3, "Lasanha à Bolonhesa", 38.00, "Camadas de massa, molho bolonhesa, presunto, queijo e molho branco.", 30, 10));
        menu.add(new ItemMenu("Bebida", 4, "Suco de Laranja Natural", 12.00, "Suco fresco de laranjas selecionadas.", 5, 10));
        menu.add(new ItemMenu("Prato", 5, "Salmão Grelhado", 65.00, "Filé de salmão grelhado com legumes salteados.", 25, 10));

        System.out.println("--- Cardápio do Restaurante ---");
        for (ItemMenu item : menu) {
            System.out.println(item);
        }
    }
}