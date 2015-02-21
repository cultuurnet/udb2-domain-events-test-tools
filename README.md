
# Installation

Install dependencies with Composer.

```
composer install
```

# Configuration

Copy config.dist.yaml to config.yaml and fill in the needed values.

# Usage

Declare the AMQP exchange with the _declare-exchange_ command.

```
 ./bin/udb2domainevents.php declare-exchange
```

Run a subscriber which will validate incoming messages with the _listen_ 
command.

```
 ./bin/udb2domainevents.php listen
```

Publish messages with the _publish_ command.


```
 ./bin/udb2domainevents.php publish \
    test \
    application/vnd.cultuurnet.udb2-events.event-updated+json \
    samples/application/vnd.cultuurnet.udb2-events.event-updated+json;
```
