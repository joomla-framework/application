## Updating from v3 to v4

The following changes were made to the Application package between v3 and v4.

### Minimum supported PHP version raised

All Framework packages now require PHP 8.3 or newer.

### Status on redirect

When calling `$app->redirect()`, you can not hand over a boolean value for the status, but always have to use an integer representing the HTTP status code.
