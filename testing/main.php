<?php

/*
 * Test this package fully works. This is just a script for now because testing is so small, but
 * if necessary, put all the tests in the "tests" folder and kick them off from here.
 */

require_once(__DIR__ . '/settings.php');
require_once(__DIR__ . '/../vendor/autoload.php');



# Test logging to a queue.
$logger = new iRAP\RabbitmqLogger\RabbitmqQueueLogger(
    RABBITMQ_HOST,
    RABBITMQ_USER,
    RABBITMQ_PASSWORD,
    RABBITMQ_QUEUE_NAME
);



$logger->debug("this is a plain debug log for a queue");
$logger->error("this is an error log for a queue with context", array('hello' => 'world'));


# Now test the exchange logging works.
$exchangeLogger = new iRAP\RabbitmqLogger\RabbitmqExchangeLogger(
    RABBITMQ_HOST, 
    RABBITMQ_USER, 
    RABBITMQ_PASSWORD, 
    RABBITMQ_EXCHANGE_NAME
);


$exchangeLogger->debug("this is a plain debug log for an exchange");
$exchangeLogger->debug("this is a plain debug log for an exchange with context", array('hello' => 'world'));


