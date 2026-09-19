# CommsPliant PHP SDK

Official PHP client for the [CommsPliant Customer Integration API](https://developer.commspliant.com/).

Requires PHP 8.1 or later with the `curl` and `json` extensions.

## Installation

```bash
composer install
```

Or clone this repository and add it to your project's `composer.json`.

## Quickstart

```php
<?php

use CommsPliant\Client;
use CommsPliant\RenderRequest;

$client = new Client('ck_YOUR_API_KEY');

$result = $client->renderHtml(new RenderRequest(
    templateId: '550e8400-e29b-41d4-a716-446655440000',
    variables: [
        'title' => 'Monthly Report',
        'user' => ['name' => 'Jane Doe'],
    ],
));

file_put_contents('document.html', $result->body);
```

## Authentication

By default the SDK sends `X-Api-Key: ck_...`.

```php
$client = new Client('ck_YOUR_API_KEY', useBearerAuth: true);
```

## Configuration

```php
$client = new Client('ck_YOUR_API_KEY', baseUrl: 'http://localhost:8085');
```

## Errors

Non-success API responses throw `APIException` with `statusCode`, message, and `requestId`.

## Documentation

Endpoint guides and SDK usage examples: [doc/README.md](doc/README.md)

## Links

- [Developer Portal](https://developer.commspliant.com/)
- [About CommsPliant](https://commspliant.com/)
