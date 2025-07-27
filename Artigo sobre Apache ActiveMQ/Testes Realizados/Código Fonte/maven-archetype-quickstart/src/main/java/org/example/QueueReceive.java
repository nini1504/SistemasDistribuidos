package org.example;

import jakarta.jms.*;
import org.apache.activemq.ActiveMQConnectionFactory;

public class QueueReceive {

    private static final String BROKER_URL = "tcp://localhost:61616";
    private static final String QUEUE_NAME = "minha.primeira.fila"; // Mesmo nome da fila do produtor

    public static void main(String[] args) {
        ConnectionFactory connectionFactory = null;
        Connection connection = null;
        Session session = null;
        MessageConsumer consumer = null;

        try {
            // 1. Crie uma ConnectionFactory específica do ActiveMQ
            connectionFactory = new ActiveMQConnectionFactory(BROKER_URL);

            // 2. Crie uma conexão JMS
            connection = connectionFactory.createConnection();
            connection.start(); // Inicie a conexão

            // 3. Crie uma sessão JMS
            session = connection.createSession(false, Session.AUTO_ACKNOWLEDGE);

            // 4. Crie o destino da mensagem (a fila)
            Destination destination = session.createQueue(QUEUE_NAME);

            // 5. Crie um consumidor de mensagens para o destino
            consumer = session.createConsumer(destination);

            System.out.println("--- Consumidor Pronto para Receber Mensagens ---");
            System.out.println("Esperando mensagens na fila: '" + QUEUE_NAME + "' em " + BROKER_URL);
            System.out.println("Pressione Ctrl+C para encerrar o consumidor a qualquer momento.");

            // 6. Loop para receber mensagens continuamente
            while (true) {
                // receive(timeout): Espera por uma mensagem por no máximo 10 segundos (10000 ms)
                // receive(): Espera indefinitivamente por uma mensagem (pode bloquear a thread)
                Message message = consumer.receive(10000); // Tenta receber por 10 segundos

                if (message == null) {
                    System.out.println("  [INFO] Nenhuma mensagem recebida em 10 segundos. Continuo esperando...");
                    continue; // Continua o loop para esperar mais
                }

                // 7. Processa a mensagem recebida
                if (message instanceof TextMessage) {
                    TextMessage textMessage = (TextMessage) message;
                    String text = textMessage.getText();
                    System.out.println("  [RECEBIDO] Mensagem: '" + textMessage.getText() + "'");
                } else {
                    System.out.println("  [RECEBIDO] Mensagem de outro tipo: " + message.getClass().getName());
                }
            }

        } catch (JMSException e) {
            // Capture e imprima quaisquer erros de JMS
            System.err.println("Erro na comunicação JMS para o consumidor: " + e.getMessage());
            e.printStackTrace();
        } finally {
            // Sempre feche os recursos em um bloco finally para garantir que sejam liberados
            try {
                if (consumer != null) consumer.close();
                if (session != null) session.close();
                if (connection != null) connection.close();
            } catch (JMSException e) {
                System.err.println("Erro ao fechar recursos: " + e.getMessage());
            }
            System.out.println("Consumidor encerrado e recursos liberados.");
        }
    }
}