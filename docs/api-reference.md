# API reference

Every public and protected member of the package, grouped by class.

## `ApplicationInterface`

| Method | Returns | Description |
|---|---|---|
| `close($code = 0)` | never | Terminate the application with an exit code |
| `execute()` | `void` | Run the application |

## `ConfigurationAwareApplicationInterface extends ApplicationInterface`

| Method | Returns | Description |
|---|---|---|
| `get($key, $default = null)` | `mixed` | Read a configuration value |
| `set($key, $value = null)` | `mixed` | Write a value, returns the previous one |
| `setConfiguration(Registry $config)` | `$this` | Replace the whole configuration |

## `AbstractApplication`

Implements `ConfigurationAwareApplicationInterface`, `LoggerAwareInterface`,
`DispatcherAwareInterface`. Uses `LoggerAwareTrait` and `DispatcherAwareTrait`.

| Member | Visibility | Description |
|---|---|---|
| `__construct(?Registry $config = null)` | public | Sets config, writes the `execution.*` keys, calls `initialise()` |
| `execute()` | public | Dispatches `before_execute`, calls `doExecute()`, dispatches `after_execute`; catches `Throwable` into `application.error` |
| `close($code = 0)` | public | `exit($code)` |
| `get($key, $default = null)` | public | Configuration read |
| `set($key, $value = null)` | public | Configuration write, returns previous value |
| `setConfiguration(Registry $config)` | public | Fluent |
| `getLogger()` | public | Returns the logger, installing a `NullLogger` on first use |
| `setLogger(LoggerInterface $logger)` | public | From `LoggerAwareTrait` |
| `getDispatcher()` / `setDispatcher()` | public | From `DispatcherAwareTrait` |
| `doExecute()` | protected, **abstract** | Your application logic |
| `initialise()` | protected | Empty hook, called at the end of the constructor |
| `dispatchEvent(string $eventName, ?EventInterface $event = null)` | protected | Dispatches, or returns `null` when no dispatcher is set |

## `AbstractWebApplication extends AbstractApplication`

Implements `WebApplicationInterface`.

### Public properties

| Property | Type | Default | Used for |
|---|---|---|---|
| `$charSet` | `string` | `'utf-8'` | Appended to `Content-Type` |
| `$mimeType` | `string` | `'text/html'` | The `Content-Type` header |
| `$httpVersion` | `string` | `'1.1'` | The status line |
| `$modifiedDate` | `?DateTime` | `null` | `Last-Modified` when caching is allowed |
| `$client` | `Web\WebClient` | detected | User agent information |

### Methods

| Member | Visibility | Description |
|---|---|---|
| `__construct(?Input, ?Registry, ?WebClient, ?ResponseInterface)` | public | All arguments optional; calls `loadSystemUris()` |
| `execute()` | public | Full lifecycle including `compress()` and `respond()` |
| `getInput()` | public | The `Input` object |
| `redirect($url, $status = 303)` | public | Sends a redirect and calls `close()` |
| `allowCache($allow = null)` | public | Getter with `null`, setter otherwise; returns the resulting state |
| `setHeader($name, $value, $replace = false)` | public | Fluent |
| `getHeaders()` | public | List of `['name' => …, 'value' => …]` |
| `clearHeaders()` | public | Removes every header, fluent |
| `sendHeaders()` | public | Sends status line and headers, fluent |
| `setBody($content)` | public | Replaces the body, fluent |
| `prependBody($content)` | public | Fluent |
| `appendBody($content)` | public | Fluent |
| `getBody()` | public | The body as a string |
| `getResponse()` | public | The PSR-7 `ResponseInterface` |
| `setResponse(ResponseInterface $response)` | public | `void` |
| `isValidHttpStatus($code)` | public | Whether the code is in the known status map |
| `isSslConnection()` | public | Based on `$_SERVER['HTTPS']` and the port only |
| `compress()` | protected | gzip/deflate the body based on `Accept-Encoding` |
| `respond()` | protected | Content-Type, cache headers, status, then `sendHeaders()` and echo the body |
| `getHttpStatusValue($value)` | protected | Maps an integer to a status line |
| `checkConnectionAlive()` | protected | `connection_status() === CONNECTION_NORMAL` |
| `checkHeadersSent()` | protected | `headers_sent()` |
| `detectRequestUri()` | protected | Builds the request URI from the server environment |
| `header($string, $replace = true, $code = null)` | protected | Wraps `header()`, strips NUL bytes |
| `isRedirectState($state)` | protected | Whether the code is a 3xx in the status map |
| `loadSystemUris($requestUri = null)` | protected | Writes the `uri.*` configuration keys |

## `WebApplication extends AbstractWebApplication`

Implements `SessionAwareWebApplicationInterface`, uses `SessionAwareWebApplicationTrait`.

| Member | Visibility | Description |
|---|---|---|
| `__construct(ControllerResolverInterface, RouterInterface, ?Input, ?Registry, ?WebClient, ?ResponseInterface)` | public | |
| `doExecute()` | protected | Parses the route, merges route variables into the input with `def()`, calls the controller |

## `WebApplicationInterface extends ConfigurationAwareApplicationInterface`

`getInput()`, `redirect()`, `allowCache()`, `setHeader()`, `getHeaders()`, `clearHeaders()`,
`sendHeaders()`, `setBody()`, `prependBody()`, `appendBody()`, `getBody()`, `getResponse()`,
`isValidHttpStatus()`, `setResponse()`, `isSslConnection()`.

## `SessionAwareWebApplicationInterface extends WebApplicationInterface`

| Method | Returns | Description |
|---|---|---|
| `getSession()` | `SessionInterface` | Throws `RuntimeException` when unset |
| `setSession(SessionInterface $session)` | `$this` | |
| `checkToken($method = 'post')` | `bool` | Validates the CSRF token; throws `InvalidArgumentException` for a method other than `post`/`get` |
| `getFormToken($forceNew = false)` | `string` | The session token |

## `ApplicationEvents`

| Constant | Value |
|---|---|
| `ERROR` | `application.error` |
| `BEFORE_EXECUTE` | `application.before_execute` |
| `AFTER_EXECUTE` | `application.after_execute` |
| `BEFORE_RESPOND` | `application.before_respond` |
| `AFTER_RESPOND` | `application.after_respond` |

## `Event\ApplicationEvent extends Joomla\Event\Event`

| Method | Returns |
|---|---|
| `__construct(string $name, AbstractApplication $application)` | |
| `getApplication()` | `AbstractApplication` |

## `Event\ApplicationErrorEvent extends ApplicationEvent`

| Method | Returns |
|---|---|
| `__construct(Throwable $error, AbstractApplication $application)` | |
| `getError()` | `Throwable` |
| `setError(Throwable $error)` | `void` |

## `Controller\ControllerResolverInterface`

| Method | Returns |
|---|---|
| `resolve(ResolvedRoute $route)` | `callable`, throws `InvalidArgumentException` |

## `Controller\ControllerResolver`

| Member | Visibility | Description |
|---|---|---|
| `resolve(ResolvedRoute $route)` | public | See [Routing and controllers](routing-and-controllers.md#controllerresolver) |
| `instantiateController(string $class)` | protected | `new $class()` |

## `Controller\ContainerControllerResolver extends ControllerResolver`

| Member | Visibility | Description |
|---|---|---|
| `__construct(ContainerInterface $container)` | public | |
| `instantiateController(string $class)` | protected | Container first, then `parent::` |

## `Web\WebClient`

| Member | Description |
|---|---|
| `__construct($userAgent = null, $acceptEncoding = null, $acceptLanguage = null)` | Falls back to the `$_SERVER` values |
| `__get($name)` | Lazily detects and returns `platform`, `mobile`, `engine`, `browser`, `browserVersion`, `language`, `encoding`, `robot`, `userAgent`, `detection` |

Platform constants: `WINDOWS`, `WINDOWS_PHONE`, `WINDOWS_CE`, `IPHONE`, `IPAD`, `IPOD`, `MAC`,
`BLACKBERRY`, `ANDROID`, `LINUX`, `ANDROIDTABLET`.
Engine constants: `TRIDENT`, `WEBKIT`, `GECKO`, `PRESTO`, `KHTML`, `AMAYA`, `BLINK`.
Browser constants: `IE`, `FIREFOX`, `CHROME`, `SAFARI`, `OPERA`, `EDGE`, `EDG`.

## `Exception\UnableToWriteBody`

Extends `RuntimeException`. Thrown by `setBody()`, `prependBody()` and `appendBody()` when the
PSR-7 body stream is not writable.
