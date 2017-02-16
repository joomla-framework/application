## Updating from v1 to v2

The following changes were made to the Application package between v1 and v2.

### PHP 5.3 and 5.4 support dropped

The Session package now requires PHP 5.5 or newer.

### PSR-7 Responses now Supported
In order to support PSR-7 responses there is a single break in backwards incompatibility.
The `\Joomla\Application\AbstractWebApplication::getBody()` method does not have a
`toBody` parameter. Joomla will default to use Zend Framework's Diactoros package
 if none is provided.
 
 If you wish to provide another package's response object then simply inject it into the
 constructor of `\Joomla\Application\AbstractWebApplication`

