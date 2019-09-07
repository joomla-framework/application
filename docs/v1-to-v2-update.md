## Updating from v1 to v2

The following changes were made to the Application package between v1 and v2.

### Minimum supported PHP version raised

All Framework packages now require PHP 7.2 or newer.

### PSR-7 Responses now supported
In order to support PSR-7 responses there is a single break in backwards incompatibility. The `\Joomla\Application\AbstractWebApplication::getBody()` method does not have a `toBody` parameter.

The package internals use the Zend Framework [Diactoros package](https://github.com/zendframework/zend-diactoros) for building the Response object. If you wish to use another PSR-7 compatible library you will need to extend the `setHeader`, `getHeaders`, `clearHeaders`, `setBody`, `prependBody`, and `appendBody` methods.

### AbstractWebApplication::checkToken now validates a token
The method `\Joomla\Application\AbstractWebApplication::checkToken` has been changed to validate a token in addition to checking if it is present in the request. Additionally, the homepage redirect on an invalid token has been removed.

### CLI Classes Removed

The `\Joomla\Application\AbstractCliApplication` and all `Joomla\Application\Cli` namespace classes have been removed. The new `joomla/console` package should be used going forward.

### Added a concrete web application

There is a new `\Joomla\Application\WebApplication` class available which serves as a minimal but functional web application class. This class extends `\Joomla\Application\AbbstractSessionAwareWebApplication` and therefore makes all application features available out-of-the-box.

### `$input` property moved to web application classes

The `$input` property of `\Joomla\Application\AbstractApplication` has been moved to `\Joomla\Application\AbstractWebApplication` and is no longer required to create a minimal application. With the introduction of the `joomla/console` package, which does not use the `joomla/input` package to read the console input, it is no longer practical to require all application classes support this input API.

Additionally, direct access to the property has been deprecated. To access the input, you should use the `\Joomla\Application\AbstractWebApplication::getInput()` method. Direct read access to the property will be removed in 3.0.

### Session functionality moved to new application subclass

As sessions are not a mandatory function of web applications, session related functionality has been moved to a new `\Joomla\Application\AbbstractSessionAwareWebApplication` class extending `\Joomla\Application\AbstractWebApplication`. If your application requires session support, you should extend the new class.

### Interfaces for application classes

Interfaces have been created for the application classes with the following structure:

- `\Joomla\Application\ApplicationInterface` defines the base requirements for all applications
- `\Joomla\Application\ConfigurationAwareApplicationInterface` defines an application which is aware of a configuration object
- `\Joomla\Application\WebApplicationInterface` defines a web application handling HTTP requests and serving HTTP responses
- `\Joomla\Application\SessionAwareWebApplicationInterface` defines a web application which requires session support
