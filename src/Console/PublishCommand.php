<?php
/**
 * @file
 */

namespace CultuurNet\UDB2DomainEventsTestTools\Console;

use Cilex\Command\Command;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\Identity\UUID;

class PublishCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('publish')
            ->setDescription('Publishes a message to the message broker')
            ->addArgument(
                'routing-key',
                InputArgument::REQUIRED,
                'The routing-key to add to the message'
            )
            ->addArgument(
                'content-type',
                InputArgument::REQUIRED,
                'The content-type of the message'
            )
            ->addArgument(
                'file-path',
                InputArgument::REQUIRED,
                'Path to the file containing the message-body'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getAmqpConnection();

        $channel = $connection->channel();

        $message = $this->createMessage($input);

        $routingKey = $input->getArgument('routing-key');

        $config = $this->getService('config');
        $exchange = $config['amqp']['exchange'];

        $channel->basic_publish(
            $message,
            $exchange,
            $routingKey
        );

        $output->writeln(
            'message published with correlation_id ' .
            $message->get('correlation_id')
        );
    }

    /**
     * @return AMQPConnection
     */
    private function getAmqpConnection()
    {
        return $this->getService('amqp');
    }

    /**
     * @param InputInterface $input
     * @return AMQPMessage
     */
    private function createMessage(InputInterface $input)
    {
        $contentType = $input->getArgument('content-type');
        $filePath = $input->getArgument('file-path');
        $body = file_get_contents($filePath);
        $correlationId = UUID::generateAsString();

        $message = new AMQPMessage();
        $message->setBody($body);
        $message->set('content_type', $contentType);
        $message->set('correlation_id', $correlationId);

        return $message;
    }
}
