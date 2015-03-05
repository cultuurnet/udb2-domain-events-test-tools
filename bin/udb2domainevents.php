#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Cilex\Application;
use CultuurNet\UDB2DomainEventsTestTools\Console\DeclareExchangeCommand;
use CultuurNet\UDB2DomainEventsTestTools\Console\ListenCommand;
use CultuurNet\UDB2DomainEventsTestTools\Console\PublishCommand;
use PhpAmqpLib\Connection\AMQPConnection;
use ValueObjects\String\String;

$cwd = getcwd();

$app = new Application('UDB2 Domain Events Test Tools');

$app['config'] = $app->share(
    function () use ($cwd) {
        $parser = new \Symfony\Component\Yaml\Parser();
        $config = $parser->parse(file_get_contents($cwd . '/config.yaml'));

        return $config;
    }
);

$app['amqp'] = $app->share(
    function (Application  $app) {
        $config = $app['config']['amqp'];

        $connection = new AMQPConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        );

        $connection->set_close_on_destruct();

        return $connection;
    }
);

$app['deserializerLocator'] = $app->share(
    function () {
        $deserializerLocator = new \CultuurNet\Deserializer\SimpleDeserializerLocator();

        $deserializerLocator->registerDeserializer(
            new String(
                'application/vnd.cultuurnet.udb2-events.event-created+json'
            ),
            new \CultuurNet\UDB2DomainEvents\EventCreatedJSONDeserializer()
        );

        $deserializerLocator->registerDeserializer(
            new String(
                'application/vnd.cultuurnet.udb2-events.event-updated+json'
            ),
            new \CultuurNet\UDB2DomainEvents\EventUpdatedJSONDeserializer()
        );

        $deserializerLocator->registerDeserializer(
            new String(
                'application/vnd.cultuurnet.udb2-events.actor-created+json'
            ),
            new \CultuurNet\UDB2DomainEvents\ActorCreatedJSONDeserializer()
        );

        $deserializerLocator->registerDeserializer(
            new String(
                'application/vnd.cultuurnet.udb2-events.actor-updated+json'
            ),
            new \CultuurNet\UDB2DomainEvents\ActorUpdatedJSONDeserializer()
        );

        return $deserializerLocator;
    }
);

$app->command(new ListenCommand());
$app->command(new PublishCommand());
$app->command(new DeclareExchangeCommand());

$app->run();
