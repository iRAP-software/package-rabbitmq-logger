<?php

/*
 * A class for logging thing to an exchange rather than a queue. With an exchange, the message
 * is pushed out to all subscribers and "disappears" immediately afterwards.
 */

class RabbitmqExchangeLogger implements \iRAP\Logging\LoggerInterface
{
    private $m_channel;
    
    
    /**
     * Create the RabbitmqExchangeLogger.
     * @param string $host - the host where the RabbitMQ server is.
     * @param string $username - the username to connect to RabbitMQ with.
     * @param string $password - the password to connect with.
     * @param string $exchangeName - the name of the exchange to publish to.
     * @param int $port - optional - the port of the server. Defaults to 5672.
     */
    public function __construct($host, $username, $password, $exchangeName, $port=5672)
    {
        $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
            $host, 
            $port, 
            $username, 
            $password
        );

        $this->m_channel = $connection->channel();
        
        # Create the exchange if it doesnt exist already.
        $this->m_channel->exchange_declare(
            $exchangeName, 
            'fanout', # type
            false,    # passive
            false,    # durable
            false     # auto_delete
        );
    }
    
    
    public function alert($message, array $context = array()) 
    {
        $this->log(\iRAP\Logging\LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = array()) 
    {
        $this->log(\iRAP\Logging\LogLevel::CRITICAL, $message, $context);
    }

    public function debug($message, array $context = array()) 
    {
        $this->log(\iRAP\Logging\LogLevel::DEBUG, $message, $context);
    }

    public function emergency($message, array $context = array()) 
    {
        $this->log(\iRAP\Logging\LogLevel::EMERGENCY, $message, $context);
    }

    public function error($message, array $context = array()) 
    {
        $this->log(\iRAP\Logging\LogLevel::ERROR, $message, $context);
    }

    public function info($message, array $context = array()) 
    {
        $this->log(\iRAP\Logging\LogLevel::INFO, $message, $context);
    }
    
    
    /**
     * Log something. This is the "base" method used by all other methods in this class.
     * @param int $level - the level of the log, higher is more urgent. Use \iRAP\Logging\LogLevel
     * @param string $message - message to log.
     * @param array $context - array of any extra context to log
     */
    public function log($level, $message, array $context = array()) 
    {
        $logArray = array(
            'level' => $level,
            'timestamp' => time(),
            'message' => $message,
            'context' => $context
        );
        
        $logString = json_encode($logArray);
        $msg = new \PhpAmqpLib\Message\AMQPMessage($logString);
        $this->m_channel->basic_publish($msg, EXCHANGE_NAME);
    }

    public function notice($message, array $context = array()) 
    {
        $this->log(\iRAP\Logging\LogLevel::NOTICE, $message, $context);
    }

    public function warning($message, array $context = array()) 
    {
        $this->log(\iRAP\Logging\LogLevel::WARNING, $message, $context);
    }
}