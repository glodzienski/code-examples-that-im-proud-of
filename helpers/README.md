# Laravel Helpers

## Functionalities Namespace

In the `Functionalities` folder, there are several traits that I created to make it easy to work with data classes like DTOs. These traits provide actions within the DTO classes. For example:

- **PropertiesAttacherFunctionality.php**: This trait provides the `attachValues` function, which allows you to set properties of a DTO class by passing an array. The array values will be mapped to the DTO's properties.

- **PropertiesExporterFunctionality.php**: By using this trait, you gain access to the `properties` function, which returns all the properties that exist within your DTO class. Essentially, this will return all fields in your DTO.

- **ValuesExporterToArrayFunctionality.php**: This trait allows you to call the `toArray` function, which exports all properties of the class to an associative array.

- **ValuesExporterToJsonFunctionality.php**: Similar to the `toArray` trait but exports the properties to a JSON string instead.

- **ValuesExporterToSnakeFunctionality.php**: This trait works similarly to the previous two, but it transforms the keys of the response array into `snake_case` rather than `camelCase`.

## HTTP Helper

The `HttpHelper` class is designed to centralize all HTTP requests. Instead of directly using `CURL`, `Guzzle`, or any other HTTP client, you use this `HttpHelper` class. It standardizes various factors, including the HTTP request method, logging, and more.

In addition to standardization and organization, this class provides:

- **Request Logs**: It logs when a request is made from your API, how long the request took, and whether it succeeded or failed.

- **Testing Support**: One of the most important features is its excellent support for unit testing. You can mock responses for requests made through `HttpHelper` in the `mockedEndpoints` file. This allows you to unit test with various use cases by simulating responses from external APIs. In unit tests, you can also force errors like `400`, `401`, `404`, or `500` responses to ensure your application handles them correctly.

---

## Why I'm Proud of This Code

I am particularly proud of the **Functionalities** helpers because I always admired how frameworks like Laravel or even Eloquent have well-constructed helper classes that can be used by various types of classes. I thought it would be incredible to have a trait that you can easily use to get functionality without tying it to the class itself. Thinking about these functionalities was also when I learned about traits for the first time, and I thought, "This is genius, exactly what I need!" Something simple to use without the overhead of a base class or an interface that wouldn't implement the method itself. It was a major learning moment for me, and I’m very proud of the simplicity and power this design brings to the project.

As for the **HttpHelper**, I’m equally proud of it because it solves many practical problems in an elegant way. From standardization to providing request logs and an internal mock server for testing, this class demonstrates how creative thinking can lead to valuable tools. It allows developers to thoroughly test their APIs under various conditions, such as handling errors, and has become a key element in projects within my company.