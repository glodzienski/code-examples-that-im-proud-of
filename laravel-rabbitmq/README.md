### Overview

All the files in the **Core** namespace are abstract classes meant to represent entities that exist throughout the RabbitMQ process. These classes handle core functionality that is not intended to be altered and will never be instantiated directly, but rather extended.

- `laravel-rabbitmq/app/Core/BaseConsumer.php`: Represents and contains functions related to the consumer.
- `laravel-rabbitmq/app/Core/BaseConsumerConfigurator.php`: Represents and handles the number of consumers and determines which handler will be used to consume messages for this consumer.
- `laravel-rabbitmq/app/Core/BaseDto.php`: Represents and contains properties related to the message that will be posted and consumed.
- `laravel-rabbitmq/app/Core/BaseExchange.php`: Represents and contains functions related to the exchange.
- `laravel-rabbitmq/app/Core/BasePublisher.php`: Represents and contains functions related to the publisher.
- `laravel-rabbitmq/app/Core/BaseQueue.php`: Represents and contains functions related to the queue.

The classes in the **Example** namespace are an example of the necessary classes for a full publication and consumption process. They extend the core classes, which enforce required configurations, validate them, and integrate RabbitMQ's library seamlessly.

- `laravel-rabbitmq/app/Example/ExampleConsumer.php`
- `laravel-rabbitmq/app/Example/ExampleConsumerConfigurator.php`
- `laravel-rabbitmq/app/Example/ExampleExchange.php`
- `laravel-rabbitmq/app/Example/ExampleHandler.php`
- `laravel-rabbitmq/app/Example/ExamplePayloadDto.php`
- `laravel-rabbitmq/app/Example/ExamplePublisher.php`
- `laravel-rabbitmq/app/Example/ExampleQueue.php`

### Package Connector

The **Connector** class exists to manage the connection between the PHP server and the RabbitMQ server in the most efficient way possible. This class solves some common issues found between PHP and RabbitMQ.

It uses the **Singleton** pattern to prevent unnecessary connection creation. It handles reconnection in case of connection failures and manages memory cleanup and open connections in its destructor.

- `laravel-rabbitmq/app/Connectors/PackageConnector.php`

### Service Provider

The package includes a **Service Provider**, which configures certain key elements behind the scenes. By simply using the service provider, your Laravel application will be fully integrated with RabbitMQ. For instance, it sets up a dedicated log channel for this package and RabbitMQ in general. The main highlight is how this package manages consumers: the service provider automatically starts up the configured consumers listed in the configuration file.

- `laravel-rabbitmq/app/Providers/PackageQueuesServiceProvider.php`

### Laravel Command

This package includes a **Laravel Command**, which is designed to be registered as a cron job by the service provider. The command aggregates everything necessary for a consumer to function, including:
- The consumer itself.
- The consumer handler that will process the message.
- It handles parallel consumers if needed, and you can set a TTL (Time to Live) for the consumer process, allowing it to be restarted automatically after expiration.

The command also implements **Singleton** for performance and ensures proper configuration validation.

### Conclusion

While there are packages out there that integrate RabbitMQ with Laravel, I believe the key differentiator of this package is its strong focus on object-oriented design. It avoids massive arrays or deeply nested text-based configurations, which are often prone to errors in RabbitMQ workflows, potentially leading to critical issues in a business process.

Additionally, the goal is to avoid the need for fixed cron job entries in `crontab` files. Instead, I worked extensively to provide a robust solution using Laravel's built-in tools to dynamically spawn consumers and their clones as needed.

### Why I Am Proud of This Code

One of the main reasons I'm proud of this code is that it's a package. Throughout my career, I’ve always used external packages in my projects and admired how convenient and powerful they were. Creating a package of my own is something that makes me extremely proud. It marks a significant milestone in my development journey, transforming from a consumer of packages to a creator.

Additionally, at the time I researched and developed this package, I found that there wasn’t anything quite like it available. This motivated me to build something truly innovative that would solve a gap I identified in the ecosystem.

What makes this package stand out is how it leverages various architectural concepts and abstraction principles from RabbitMQ and integrates them into Laravel. Rather than relying on static, procedural code, I turned it into a fully object-oriented solution, making it much more scalable, maintainable, and flexible. The connection to the Laravel framework is seamless, which also adds to its versatility.

This project is not just about functionality but also about bringing clean, organized, and maintainable code to an area that is often cluttered with procedural, hard-to-manage logic. It’s a reflection of my commitment to high-quality code and design patterns.