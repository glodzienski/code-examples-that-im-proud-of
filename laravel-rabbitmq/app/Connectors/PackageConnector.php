<?php

namespace Package\Queues\Connectors;

use Exception;
use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\Queues\Enums\ExceptionEnum;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class PackageConnector
 * @package Package\Queues\Connectors
 */
class PackageConnector
{
    /**
     * @var AMQPStreamConnection[]
     */
    private static $connections;

    /**
     * @var string
     */
    private const DEFAULT_CONNECTION = 'name';

    /**
     * @param string|null $connectionName
     * @return AMQPStreamConnection
     * @throws BadImplementationException
     */
    public static function connection(?string $connectionName = null): AMQPStreamConnection
    {
        $connectionName = $connectionName ?? self::DEFAULT_CONNECTION;

        self::validateConnectionConfiguration($connectionName);

        if (!is_null(self::$connections) && isset(self::$connections[$connectionName])) {
            $connection = self::$connections[$connectionName];

            if (!$connection->isConnected()) {
                $connection->reconnect();
            }

            return $connection;
        }

        $connectionsSettings = config('rabbitmq.connections', []);
        $connectionSetting = $connectionsSettings[$connectionName];

        if (isset($connectionSetting['ssl_connection']) && $connectionSetting['ssl_connection']) {
            $vhost = '/';
            $sslOptions = [
                'verify_peer' => false
            ];

            self::$connections[$connectionName] = new AMQPSSLConnection(
                $connectionSetting['host'],
                $connectionSetting['port'],
                $connectionSetting['user'],
                $connectionSetting['password'],
                $vhost,
                $sslOptions
            );
        } else {
            self::$connections[$connectionName] = new AMQPStreamConnection(
                $connectionSetting['host'],
                $connectionSetting['port'],
                $connectionSetting['user'],
                $connectionSetting['password'],
                $vhost = '/',
                $insist = false,
                $login_method = 'AMQPLAIN',
                $login_response = null,
                $locale = 'en_US',
                $connection_timeout = 3.0,
                $read_write_timeout = 3.0,
                $context = null,
                $keepalive = false,
                $connectionSetting['heartbeat'] ?? 10
            );
        }

        return self::$connections[$connectionName];
    }

    /**
     * @param string|null $connectionName
     * @throws BadImplementationException
     */
    private static function validateConnectionConfiguration(?string $connectionName = null): void
    {
        $queuesSettings = config('rabbitmq');
        if (empty($queuesSettings)) {
            throw new BadImplementationException(
                ExceptionEnum::CONNECTION_BAD_IMPLEMENTATION,
                'You must configure a file called queues in config path.'
            );
        }

        if (!is_null($connectionName) && isset($connectionsSettings[$connectionName])) {
            $mainConnection = $connectionsSettings[$connectionName];
        } else {
            $mainConnection = array_shift($queuesSettings['connections']) ?? [];
        }

        if (
            empty($mainConnection['host'])
            || empty($mainConnection['port'])
            || empty($mainConnection['user'])
            || empty($mainConnection['password'])
        ) {
            throw new BadImplementationException(
                ExceptionEnum::CONNECTION_BAD_IMPLEMENTATION,
                'Your queues file configuration has some issues.'
            );
        }
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        self::shutdownAll();
    }

    /**
     * @throws Exception
     */
    public static function shutdownAll(): void
    {
        if (isset(self::$connections)) {
            foreach (self::$connections as $connection) {
                if ($connection->isConnected()) {
                    $connection->close();
                }
            }

            self::$connections = null;
        }
    }
}
