package mensageria;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

public class NetworkUtils {

    private static final ObjectMapper objectMapper = new ObjectMapper();

    /**
     * Converte um objeto Java para uma string JSON.
     * @param objeto O objeto a ser serializado.
     * @return Uma string JSON, ou null se ocorrer um erro.
     */
    public static String serializeToJson(Object objeto) {
        try {
            return objectMapper.writeValueAsString(objeto);
        } catch (JsonProcessingException e) {
            e.printStackTrace();
            return null;
        }
    }
}