<?php

namespace Package\Queues\Enums;

/**
 * Class ExchangeTypeEnum
 * @package Package\Queues\Enumerators
 */
class ExchangeTypeEnum
{
    /**
     * @var string
     */
    public const DIRECT = 'direct';
    /**
     * @var string
     */
    public const FANOUT = 'fanout';
    /**
     * @var string
     */
    public const TOPIC = 'topic';
    /**
     * @var string
     */
    public const HEADERS = 'headers';
}
