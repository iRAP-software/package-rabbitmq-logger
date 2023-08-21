<?php

/*
 * A class for logging thing to a queue rather than an exchange. With a queue, the message
 * is stored until something comes along and grabs it later. This is good if you have a logger
 * service that fetches these things and processes them, but you don't want to lose any logs if
 * that service has downtime for whatever reason.
 */

namespace iRAP\RabbitmqLogger;

class RabbitmqQueueLogger implements \iRAP\Logging\LoggerInterface
{
    private $m_queueName;
    private $m_channel;
    private $m_source = 'n/s';
    
     /**
     * Create the RabbitmqQueueLogger.
     * @param string $host - the host where the RabbitMQ server is.
     * @param string $user - the username to connect to RabbitMQ with.
     * @param string $password - the password to connect with.
     * @param string $queueName - the name of the queue to publish to.
     * @param int $port - optional - the port of the server. Defaults to 5672.
     * @param string $source - optional - the name of the project that is using this library
     */
    public function __construct($host, $user, $password, $queueName, $port=5672, $source=null) 
    {
        $this->m_queueName = $queueName;
        
        $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
            $host, 
            $port, 
            $user, 
            $password
        );
        
        $this->m_channel = $connection->channel();
        
        $this->m_channel->queue_declare(
            $queueName,
            $passive = false,
            $durable = true,
            $exclusive = false,
            $auto_delete = false,
            $nowait = false,
            $arguments = null,
            $ticket = null
        );
        
        // if the project constant is defined
        if(defined("SERVICE_NAME")) {
            $this->m_source = SERVICE_NAME;
        }
        // overwrite the value if one is actually passed to the object
        if($source) {
            $this->m_source = $source;
        }

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
        # If contextString is not JSON encodable, then just get a print_r 
        # representation of it.
        if (is_null(json_encode($context)))
        {
            $context = print_r($context, true);
        }
                
        $logArray = array(
            'level' => $level,
            'timestamp' => time(),
            'message' => $message,
            'context' => $context,
            'source' => $this->m_source
        );      
        
        $msg = new \PhpAmqpLib\Message\AMQPMessage(
            json_encode($logArray, JSON_UNESCAPED_SLASHES),
            array('delivery_mode' => 2) # make message persistent
        );
        
        $this->m_channel->basic_publish($msg, '', $this->m_queueName);
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
