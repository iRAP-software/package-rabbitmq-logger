<?php

/*
 * A class for logging thing to an exchange rather than a queue. With an exchange, the message
 * is pushed out to all subscribers and "disappears" immediately afterwards.
 */

namespace iRAP\RabbitmqLogger;

use Exception;
use iRAP\Logging\LoggerInterface;
use iRAP\Logging\LogLevel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitmqExchangeLogger implements LoggerInterface
{
    private ?AMQPChannel $m_channel = null;
    private string $m_exchangeName;
    private array $m_params;

    /**
     * Create the RabbitmqExchangeLogger.
     * @param string $host - the host where the RabbitMQ server is.
     * @param string $username - the username to connect to RabbitMQ with.
     * @param string $password - the password to connect with.
     * @param string $exchangeName - the name of the exchange to publish to.
     * @param int $port - optional - the port of the server. Defaults to 5672.
     * @throws Exception
     */
    public function __construct(string $host, string $username, string $password, string $exchangeName, int $port = 5672)
    {
        $this->m_exchangeName = $exchangeName;
        $this->m_params = [
            'host' => $host,
            'port' => $port,
            'user' => $username,
            'password' => $password
        ];
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
            'context' => $context
        );

        $logString = json_encode($logArray);
        $msg = new AMQPMessage($logString);
        $this->getChannel()->basic_publish($msg, $this->m_exchangeName);
    }

    /**
     * @throws Exception
     */
    private function getChannel(): AMQPChannel
    {
        static $connection = null;

        if ($connection === null) {
            $connection = new AMQPStreamConnection(...$this->m_params);

            $this->m_channel = $connection->channel();

            # Create the exchange if it doesn't exist already.
            $this->m_channel->exchange_declare(
                $this->m_exchangeName,
                'fanout', # type
                false,    # passive
                false,    # durable
                false     # auto_delete
            );
        }

        return $this->m_channel;
    }
}