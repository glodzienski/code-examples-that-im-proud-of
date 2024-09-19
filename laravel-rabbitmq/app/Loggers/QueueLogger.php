<?php

namespace Package\Queues\Loggers;

use Package\PhpUtils\Loggers\PackageLogger;
use Throwable;

/**
 * Class QueueLogger
 * @package Package\Queues\Loggers
 */
class QueueLogger extends PackageLogger
{
    /**
     * @var string
     */
    private const CHANNEL_LOG = 'rabbitmq_logs';
    /**
     * @var string
     */
    private $logTokenSuffix;

    /**
     * QueueLogger constructor.
     */
    public function __construct(string $logTokenSuffix = 'BAD_IMPLEMENTATION')
    {
        parent::channel(self::CHANNEL_LOG);
        $this->logTokenSuffix = $logTokenSuffix;
    }

    /**
     * @param string $token
     * @param array $context
     */
    public function info(string $token, $context = []): void
    {
        if (!config('app.debug')) {
            return;
        }

        $token = $this->buildLogToken($token, $this->logTokenSuffix);

        parent::info($token, $context);
    }

    /**
     * @param string $token
     * @param array $context
     * @param Throwable|null $exception
     */
    public function error(string $token, $context = [], Throwable $exception = null): void
    {
        $token = $this->buildLogToken($token, $this->logTokenSuffix);

        parent::error($token, $context, $exception);
    }

    /**
     * @param string $token
     * @param array $context
     * @param Throwable|null $exception
     */
    public function severe(string $token, $context = [], Throwable $exception = null): void
    {
        $token = $this->buildLogToken($token, $this->logTokenSuffix);

        parent::severe($token, $context, $exception);
    }

    /**
     * @param string $token
     * @param string $logTokenSuffix
     * @return string
     */
    private function buildLogToken(string $token, string $logTokenSuffix): string
    {
        return "{$token}__{$logTokenSuffix}";
    }
}
