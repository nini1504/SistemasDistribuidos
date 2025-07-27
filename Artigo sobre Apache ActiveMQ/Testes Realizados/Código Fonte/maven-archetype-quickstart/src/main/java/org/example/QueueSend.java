package org.example;

import jakarta.jms.*;
import org.apache.activemq.ActiveMQConnectionFactory;

import java.util.Scanner;

public class QueueSend {

    // URL padrão do ActiveMQ Classic para clientes JMS via TCP
    private static final String BROKER_URL = "tcp://localhost:61616";
    // Nome da fila que usaremos para enviar e receber mensagens
    // **IMPORTANTE:** O nome da fila aqui precisa ser o MESMO nome da fila que você usou no Consumidor
    private static final String QUEUE_NAME = "minha.primeira.fila";

    public static void main(String[] args) {
        ConnectionFactory connectionFactory = null; // Fábrica de conexões JMS
        Connection connection = null;               // Conexão com o broker
        Session session = null;                     // Sessão para criar produtor/consumidor
        MessageProducer producer = null;            // Produtor de mensagens

        try {
            // 1. Crie uma ConnectionFactory específica do ActiveMQ
            // Isso nos conecta diretamente ao broker sem precisar de JNDI complexo
            connectionFactory = new ActiveMQConnectionFactory(BROKER_URL);

            // 2. Crie uma conexão JMS usando a fábrica
            connection = connectionFactory.createConnection();
            connection.start(); // Inicie a conexão (é essencial para começar a usar)

            // 3. Crie uma sessão JMS
            //   - false: Não é uma sessão transacional (mensagens são confirmadas automaticamente)
            //   - Session.AUTO_ACKNOWLEDGE: O broker confirma a mensagem automaticamente após o envio/recebimento
            session = connection.createSession(false, Session.AUTO_ACKNOWLEDGE);

            // 4. Crie o destino da mensagem (neste caso, uma fila)
            // Se a fila não existir no broker, o ActiveMQ a criará automaticamente
            Destination destination = session.createQueue(QUEUE_NAME);

            // 5. Crie um produtor de mensagens para o destino
            producer = session.createProducer(destination);
            // Mensagens não persistentes: mais rápidas, mas podem ser perdidas se o broker falhar
            // Para garantir entrega, use DeliveryMode.PERSISTENT
            producer.setDeliveryMode(DeliveryMode.NON_PERSISTENT);

            System.out.println("--- Produtor Pronto para Enviar Mensagens ---");
            System.out.println("Enviando para a fila: '" + QUEUE_NAME + "' em " + BROKER_URL);
            System.out.println("Digite sua mensagem e pressione ENTER. Digite 'sair' para encerrar.");

            Scanner scanner = new Scanner(System.in);
            String input;
            int messageCount = 0;

            // Loop para enviar múltiplas mensagens até o usuário digitar 'sair'
            while (true) {
                System.out.print("Sua mensagem (ou 'sair'): ");
                input = scanner.nextLine();

                if ("sair".equalsIgnoreCase(input.trim())) {
                    break; // Sai do loop se o usuário digitar 'sair'
                }

                // 6. Crie uma mensagem de texto JMS
                TextMessage message = session.createTextMessage(input);

                // 7. Envie a mensagem
                producer.send(message);
                messageCount++;
                System.out.println("  [ENVIADO] Mensagem #" + messageCount + ": '" + input + "'");
            }

            scanner.close(); // Feche o scanner
            System.out.println("Produtor finalizou o envio. Total de mensagens enviadas: " + messageCount);

        } catch (JMSException e) {
            // Capture e imprima quaisquer erros de JMS
            System.err.println("Erro na comunicação JMS para o produtor: " + e.getMessage());
            e.printStackTrace();
        } finally {
            // Sempre feche os recursos em um bloco finally para garantir que sejam liberados
            try {
                if (producer != null) producer.close();
                if (session != null) session.close();
                if (connection != null) connection.close();
            } catch (JMSException e) {
                System.err.println("Erro ao fechar recursos do produtor: " + e.getMessage());
            }
            System.out.println("Produtor encerrado e recursos liberados.");
        }
    }
}