<?php

namespace Package\Queues\Enums;

/**
 * Class ExceptionEnum
 * @package Package\Queues\Enums
 */
class ExceptionEnum
{
    // BAD SETUP IMPLEMENTATION
    /**
     * @var string
     */
    public const PACKAGE_BAD_IMPLEMENTATION = 'QUE-001';

    // BAD IMPLEMENTATIONS
    /**
     * @var string
     */
    public const EXCHANGE_BAD_IMPLEMENTATION = 'QUE-002';
    /**
     * @var string
     */
    public const QUEUE_BAD_IMPLEMENTATION = 'QUE-003';
    /**
     * @var string
     */
    public const CONNECTION_BAD_IMPLEMENTATION = 'QUE-004';
    /**
     * @var string
     */
    public const PUBLISHER_BAD_IMPLEMENTATION = 'QUE-005';
    /**
     * @var string
     */
    public const CONSUMER_HANDLER_BAD_IMPLEMENTATION = 'QUE-006';
    /**
     * @var string
     */
    public const CONSUMER_BAD_IMPLEMENTATION = 'QUE-010';
    /**
     * @var string
     */
    public const CONSUMER_RAISER_BAD_IMPLEMENTATION = 'QUE-009';

    // EXECUTION ERRORS
    /**
     * @var string
     */
    public const CONSUMER_HANDLER_WITH_ERROR = 'QUE-007';
    /**
     * @var string
     */
    public const CONSUMER_EXECUTOR_WITH_ERROR = 'QUE-008';
    /**
     * @var string
     */
    public const CONSUMER_PAYLOAD_MUST_BE_ARRAY = 'QUE-011';
}
