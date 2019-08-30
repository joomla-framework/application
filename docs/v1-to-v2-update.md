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

### `$input` property moved to web application classes

The `$input` property of `\Joomla\Application\AbstractApplication` has been moved to `\Joomla\Application\AbstractWebApplication` and is no longer required to create a minimal application. With the introduction of the `joomla/console` package, which does not use the `joomla/input` package to read the console input, it is no longer practical to require all application classes support this input API.
