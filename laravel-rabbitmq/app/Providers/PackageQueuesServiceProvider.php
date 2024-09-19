<?php

namespace Package\Queues\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Package\PhpUtils\Exceptions\BadImplementationException;
use Package\PhpUtils\Facades\Logger;
use Package\Queues\Commands\ConsumerRaiserCommand;
use Package\Queues\Configurators\BaseConsumerConfigurator;
use Package\Queues\Enums\ExceptionEnum;
use Package\Queues\Enums\LogEnum;
use Throwable;

/**
 * Class PackageQueuesServiceProvider
 * @package Package\Queues\Providers
 */
class PackageQueuesServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    private const MODE_HOMOL = 'homol';

    /**
     * @var Schedule
     */
    private $schedule;

    /**
     * Register the service provider.
     * @throws BadImplementationException
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function boot(): void
    {
        $this->registerLogsSettings();

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->validateRequirements();

        $this->commands([ConsumerRaiserCommand::class,]);
        $this->schedule = $this->app->make(Schedule::class);
        $this->configureConsumers();
        $this->app->instance(
            Schedule::class,
            $this->schedule
        );
    }

    /**
     * @return void
     */
    private function registerLogsSettings(): void
    {
        $loggingSettings = config('logging', []);

        if (!array_key_exists('channels', $loggingSettings)) {
            $loggingSettings = [
                'default' => 'rabbitmq_logs',
                'channels' => [],
            ];
        }

        $todayDateString = date('Y-m-d');

        $loggingSettings['channels']['rabbitmq_logs'] = [
            'driver' => 'single',
            'path' => storage_path("logs/lumen-queues-{$todayDateString}.log"),
            'days' => 14,
        ];

        config(['logging' => $loggingSettings]);
    }

    /**
     * @throws Throwable
     */
    private function validateRequirements(): void
    {
        $rabbitmqConfiguration = config('rabbitmq', []);
        throw_if(
            empty($rabbitmqConfiguration),
            new BadImplementationException(
                ExceptionEnum::PACKAGE_BAD_IMPLEMENTATION,
                'You must configure rabbitmq configuration file in your project.'
            )
        );

        throw_if(
            empty($rabbitmqConfiguration['connections']),
            new BadImplementationException(
                ExceptionEnum::PACKAGE_BAD_IMPLEMENTATION,
                'You must configure at least one connection.'
            )
        );
    }

    /**
     * @return void
     * @throws Throwable
     */
    private function configureConsumers(): void
    {
        $consumersConfigurators = config('rabbitmq.consumers', []);
        if (empty($consumersConfigurators)) {
            Logger::info(
                LogEnum::SERVICE_PROVIDER_ZERO_CONSUMERS_IN_PROJECT,
                ['message' => 'The project has 0 consumers configured']
            );

            return;
        }

        foreach ($consumersConfigurators as $consumerConfigurator) {
            $consumerConfiguratorInstance = new $consumerConfigurator();
            throw_if(
                !($consumerConfiguratorInstance instanceof BaseConsumerConfigurator),
                new BadImplementationException(
                    ExceptionEnum::PACKAGE_BAD_IMPLEMENTATION,
                    'Your ConsumerConfigurator must instance of BaseConsumerConfigurator.'
                )
            );

            $this->registerConsumerCron($consumerConfiguratorInstance);
        }
    }

    /**
     * @param BaseConsumerConfigurator $consumerConfigurator
     * @return void
     */
    private function registerConsumerCron(
        BaseConsumerConfigurator $consumerConfigurator
    ): void {

        if (config('app.mode') === self::MODE_HOMOL) {
            $consumerConfigurator->quantity = 1;
        }

        for (
            $consumerCloneNumber = 1;
            $consumerCloneNumber <= $consumerConfigurator->quantity;
            $consumerCloneNumber++
        ) {
            $command = 'queue:consumer --consumer="' . $consumerConfigurator->consumerClass
                . '" --handler="' . $consumerConfigurator->consumerHandlerClass
                . '" --lifetime="' . $consumerConfigurator->lifetime
                . '" --clone="' . $consumerCloneNumber . '"';

            $this->schedule
                ->command($command)
                //Prevents a consumer from being lifted if it is running
                ->withoutOverlapping($consumerConfigurator->lifetime)
                //Ensures that the consumer runs in the background without crashing the project's cron
                ->runInBackground()
                ->everyMinute();
        }
    }
}
