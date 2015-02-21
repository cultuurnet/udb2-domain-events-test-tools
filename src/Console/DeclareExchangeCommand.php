<?php
/**
 * @file
 */

namespace CultuurNet\UDB2DomainEventsTestTools\Console;

use Cilex\Command\Command;
use PhpAmqpLib\Connection\AMQPConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeclareExchangeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('declare-exchange')
            ->setDescription(
                'Declares the exchange specified in the configuration file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getAmqpConnection();
        $channel = $connection->channel();

        $config = $this->getService('config');
        $exchange = $config['amqp']['exchange'];

        $channel->exchange_declare(
            $exchange,
            'topic',
            false,
            true,
            false
        );

        $output->writeln('Declared exchange ' . $exchange);
    }

    /**
     * @return AMQPConnection
     */
    private function getAmqpConnection()
    {
        return $this->getService('amqp');
    }



}
