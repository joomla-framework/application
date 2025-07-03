## Updating from v3 to v4

The following changes were made to the Application package between v3 and v4.

### Access to input data

Accessing the input data in the input attribute of the application can now only be done via the `getInput()`.

```php
// Old
$app->input->getInt();

// New
$app->getInput()->getInt();
```

### Status on redirect

When calling `$app->redirect()`, you can not hand over a boolean value for the status, but always have to use an integer representing the HTTP status code.
