<?php

/*
 * A class for logging thing to a queue rather than an exchange. With a queue, the message
 * is stored until something comes along and grabs it later. This is good if you have a logger
 * service that fetches these things and processes them, but you don't want to lose any logs if
 * that service has downtime for whatever reason.
 */

namespace iRAP\RabbitmqLogger;

use Exception;
use iRAP\Logging\LoggerInterface;
use iRAP\Logging\LogLevel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitmqQueueLogger implements LoggerInterface
{
    private string $m_queueName;
    private ?AMQPChannel $m_channel = null;
    private string $m_source = 'n/s';
    private string $m_host;
    private string $m_user;
    private string $m_password;
    private int $m_port;


    /**
     * Create the RabbitmqQueueLogger.
     * @param string $host - the host where the RabbitMQ server is.
     * @param string $user - the username to connect to RabbitMQ with.
     * @param string $password - the password to connect with.
     * @param string $queueName - the name of the queue to publish to.
     * @param int $port - optional - the port of the server. Defaults to 5672.
     * @param string|null $source - optional - the name of the project that is using this library
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $queueName,
        int $port = 5672,
        string $source = null
    )
    {
        $this->m_queueName = $queueName;
        $this->m_host = $host;
        $this->m_port = $port;
        $this->m_user = $user;
        $this->m_password = $password;

        // if the project constant is defined
        if (defined("SERVICE_NAME")) {
            $this->m_source = SERVICE_NAME;
        }
        // overwrite the value if one is actually passed to the object
        if ($source) {
            $this->m_source = $source;
        }
    }

    /**
     * @throws Exception
     */
    public function alert($message, array $context = array()): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @throws Exception
     */
    public function critical($message, array $context = array()): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @throws Exception
     */
    public function debug($message, array $context = array()): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @throws Exception
     */
    public function emergency($message, array $context = array()): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @throws Exception
     */
    public function error($message, array $context = array()): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @throws Exception
     */
    public function info($message, array $context = array()): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @throws Exception
     */
    public function notice($message, array $context = array()): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @throws Exception
     */
    public function warning($message, array $context = array()): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }


    /**
     * Log something. This is the "base" method used by all other methods in this class.
     * @param int $level - the level of the log, higher is more urgent. Use \iRAP\Logging\LogLevel
     * @param string $message - message to log.
     * @param array $context - array of any extra context to log
     * @throws Exception
     */
    public function log($level, $message, array $context = array()): void
    {
        # If contextString is not JSON encodable, then just get a print_r 
        # representation of it.
        if (is_null(json_encode($context))) {
            $context = print_r($context, true);
        }

        $logArray = array(
            'level' => $level,
            'timestamp' => time(),
            'message' => $message,
            'context' => $context,
            'source' => $this->m_source
        );

        $msg = new AMQPMessage(
            json_encode($logArray, JSON_UNESCAPED_SLASHES),
            array('delivery_mode' => 2) # make message persistent
        );

        $this->getChannel()->basic_publish($msg, '', $this->m_queueName);
    }

    /**
     * @throws Exception
     */
    private function getChannel(): AMQPChannel
    {
        static $connection = null;

        if ($connection === null) {
            $connection = new AMQPStreamConnection(
                host: $this->m_host,
                port: $this->m_port,
                user: $this->m_user,
                password: $this->m_password,
            );

            $this->m_channel = $connection->channel();

            $this->m_channel->queue_declare(
                $this->m_queueName,
                false,
                true,
                false,
                false,
                false,
                null
            );
        }

        return $this->m_channel;
    }
}
