<?php


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Package\PhpUtils\Enumerators\ErrorEnum;
use Package\PhpUtils\Enumerators\LogEnum;
use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\PhpUtils\Facades\Logger;
use Package\PhpUtils\Singletons\TracerSingleton;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Throwable;


/**
 * Class HttpHelper
 * @package Package\PhpUtils\Helpers
 * @method ResponseInterface get(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface head(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface put(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface post(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface patch(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface delete(string|UriInterface $uri, array $options = [])
 * @method \Package\PhpUtils\Helpers\Promise getAsync(string|UriInterface $uri, array $options = [])
 * @method \Package\PhpUtils\Helpers\Promise headAsync(string|UriInterface $uri, array $options = [])
 * @method \Package\PhpUtils\Helpers\Promise putAsync(string|UriInterface $uri, array $options = [])
 * @method \Package\PhpUtils\Helpers\Promise postAsync(string|UriInterface $uri, array $options = [])
 * @method \Package\PhpUtils\Helpers\Promise patchAsync(string|UriInterface $uri, array $options = [])
 * @method \Package\PhpUtils\Helpers\Promise deleteAsync(string|UriInterface $uri, array $options = [])
 */
class HttpHelper
{
    /**
     * @var bool
     */
    private static $mustReturnError = false;
    /**
     * @var array
     */
    private $clientConfig;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var int
     */
    private static $expectedHttpErrorCode = 400;

    /**
     * @var array
     */
    private $mockedEndpoints;

    /**
     * HttpHelper constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->clientConfig = $config;
        $this->client = new Client($config);
        $this->mockedEndpoints = \Package\PhpUtils\Helpers\config('mockedEndpoints') ?? [];
    }

    /**
     * @param $method
     * @param $args
     * @return ResponseInterface
     * @throws BadImplementationException
     * @throws GuzzleException
     */
    public function __call($method, $args)
    {
        $url = parse_url($args[0]);
        $urlHost = $url['host'] ?? $this->clientConfig['base_uri'] ?? '';
        $urlPath = $url['path'] ?? '';

        if (!empty($tracerValue = TracerSingleton::getTraceValue())) {
            $args = self::propagateTracerValue($tracerValue, $args);
        }

        // Handling created for mocked endpoints,
        // if there is no record in the mock contract,
        // the request will proceed as normal.
        if ($this->isTestMode()) {
            try {
                if (!$this->isMockedEndpoint($urlPath)) {
                    throw new BadImplementationException(
                        ErrorEnum::PHU003,
                        'You are requesting to external APIs in test mode. Please mock your endpoint.'
                    );
                }
                if (!class_exists($this->mockedEndpoints[$urlPath])) {
                    throw new BadImplementationException(
                        ErrorEnum::PHU004,
                        'Mock Class registered does not exist.'
                    );
                }

                $mockResponse = call_user_func_array([$this->mockedEndpoints[$urlPath], 'mock'], []);

                $httpCodeResponse = self::$mustReturnError ? self::$expectedHttpErrorCode : 200;
                $mock = new MockHandler([
                    new Response($httpCodeResponse, [], $mockResponse),
                ]);

                $handlerStack = HandlerStack::create($mock);
                $clientMock = new Client(array_merge(['handler' => $handlerStack], $this->clientConfig));

                return $clientMock->request('GET', '/');
            } finally {
                Logger::info(
                    LogEnum::REQUEST_HTTP_OUT,
                    [
                        'base_url' => $urlHost,
                        'http_url_request_out' => $urlPath,
                        'payload' => $args,
                    ]
                );
                self::$expectedHttpErrorCode = 400;
            }
        }

        $initialTime = round(microtime(true) * 1000);
        try {
            return $this->client->{$method}(...$args);
        } finally {
            $finalTime = round(microtime(true) * 1000);
            Logger::info(
                LogEnum::REQUEST_HTTP_OUT,
                [
                    'base_url' => $urlHost,
                    'http_url_request_out' => $urlPath,
                    'payload' => $args,
                    'request_time' => ($finalTime - $initialTime) / 1000,
                ]
            );
        }
    }

    /**
     * @param int $httpCode
     * @return void
     */
    public static function mustReturnError(int $httpCode = 400): void
    {
        self::$mustReturnError = true;
        self::$expectedHttpErrorCode = $httpCode;
    }

    /**
     * @return void
     */
    public static function mustNotReturnError(): void
    {
        self::$mustReturnError = false;
    }

    /**
     * @return bool
     */
    public function isTestMode(): bool
    {
        return preg_match('/test/', config('app.mode'));
    }

    /**
     * @param string $endpoint
     * @return bool
     */
    public function isMockedEndpoint(string $endpoint): bool
    {
        return !empty($endpoint) && array_key_exists($endpoint, $this->mockedEndpoints);
    }

    /**
     * @param string $tracerValue
     * @param $functionArguments
     * @return array
     */
    public static function propagateTracerValue(string $tracerValue, $functionArguments): array
    {
        $headersNamesToPropagate = \Package\PhpUtils\Helpers\config('tracer.headersToPropagate');
        $headersNamesToPropagate = is_array($headersNamesToPropagate)
            ? $headersNamesToPropagate
            : [$headersNamesToPropagate];

        $tracerHeaderToPropagate = [];
        foreach ($headersNamesToPropagate as $headerNameToPropagate) {
            $tracerHeaderToPropagate[strtolower($headerNameToPropagate)] = $tracerValue;
        }

        foreach ($functionArguments as &$functionArgument) {
            if (!is_array($functionArgument)) {
               continue;
            }

            if (in_array(RequestOptions::HEADERS, array_keys($functionArgument))) {
                $headers = $functionArgument[RequestOptions::HEADERS];
                $headers = array_merge($headers, $tracerHeaderToPropagate);

                $functionArgument[RequestOptions::HEADERS] = $headers;
                continue;
            }

            $functionArgument[RequestOptions::HEADERS] = $tracerHeaderToPropagate;
        }

        return $functionArguments;
    }
}
