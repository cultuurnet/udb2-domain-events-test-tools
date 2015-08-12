<?php
/**
 * @file
 */

namespace CultuurNet\UDB2DomainEventsTestTools\Console;

use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPConnection;
use Cilex\Command\Command;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\String\String;

class ListenCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('listen')
            ->setDescription('Listens on the message broker for new events');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getAmqpConnection();

        $channel = $connection->channel();

        // Declare an exclusive, non-durable queue for testing purposes.
        $result = $channel->queue_declare(
            '',
            false,
            false,
            true
        );

        $queueName = $result[0];
        $output->writeln('queue declared: ' . $queueName);

        $routingKey = '#';

        $config = $this->getService('config');

        $channel->queue_bind(
            $queueName,
            $config['amqp']['exchange'],
            $routingKey
        );

        $output->writeln('bound to exchange ' . $config['amqp']['exchange']);

        $output->writeln('waiting for incoming messages');
        $output->writeln('');

        $consumerTag = 'test-tools';
        $noLocal = false;
        $noAck = false;
        $exclusive = true;
        $noWait = false;

        $channel->basic_consume(
            $queueName,
            $consumerTag,
            $noLocal,
            $noAck,
            $exclusive,
            $noWait,
            function (AMQPMessage $msg) use ($output) {
                $output->writeln('message received');
                $output->writeln('routing key: ' . $msg->delivery_info['routing_key']);

                if ($msg->has('correlation_id')) {
                    $output->writeln(
                        'correlation id: ' . $msg->get('correlation_id')
                    );
                }

                $output->writeln('content type: ' . $msg->get('content_type'));
                $output->writeln('body: ' . PHP_EOL . $msg->body);

                try {
                    $deserializer = $this->getDeserializer(
                        $msg->get('content_type')
                    );

                    $deserializer->deserialize(
                        new String($msg->body)
                    );

                    $output->writeln('<info>message was recognized and well-formed</info>');
                }
                catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }

                $output->writeln('');
                $output->writeln('');
            }
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * @return AMQPConnection
     */
    private function getAmqpConnection()
    {
        return $this->getService('amqp');
    }

    /**
     * @param string $contentType
     *
     * @return DeserializerInterface
     */
    private function getDeserializer($contentType)
    {
        /** @var DeserializerLocatorInterface $deserializerLocator */
        $deserializerLocator = $this->getService('deserializerLocator');

        return $deserializerLocator->getDeserializerForContentType(
            new String($contentType)
        );
    }
}
