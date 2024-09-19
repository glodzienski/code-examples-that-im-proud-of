<?php

namespace Package\Queues\Enums;

/**
 * Class LogEnum
 * @package Package\Queues\Enums
 */
class LogEnum
{
    /**
     * @var string
     */
    public const CONSUMER_EXECUTOR_START = 'QUE-INF-001';
    /**
     * @var string
     */
    public const CONSUMER_EXECUTOR_END = 'QUE-INF-002';
    /**
     * @var string
     */
    public const CONSUMER_HANDLER_START = 'QUE-INF-003';
    /**
     * @var string
     */
    public const CONSUMER_HANDLER_END = 'QUE-INF-004';
    /**
     * @var string
     */
    public const SERVICE_PROVIDER_ZERO_CONSUMERS_IN_PROJECT = 'QUE-INF-005';
    /**
     * @var string
     */
    public const PUBLISHER_PUBLISHED_MESSAGE = 'QUE-INF-006';
}
