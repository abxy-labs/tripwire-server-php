# Tripwire PHP Library

![Preview](https://img.shields.io/badge/status-preview-111827)
![PHP 8.1+](https://img.shields.io/badge/php-%E2%89%A58.1-777BB4?logo=php&logoColor=white)
![License: MIT](https://img.shields.io/badge/license-MIT-0f766e.svg)

The Tripwire PHP library provides convenient access to the Tripwire API from applications written in PHP. It includes a framework-agnostic client for Sessions, visitor fingerprints, Teams, Gate, Team API key management, sealed token verification, and Gate delivery/webhook helpers.

The library also provides:

- a fast configuration path using `TRIPWIRE_SECRET_KEY`
- public, bearer-token, and secret-key auth modes for Gate flows
- a bundled PSR-18 transport stack with support for custom PSR clients and factories
- structured API errors, built-in sealed token verification, and Gate delivery/webhook helpers

## Documentation

See the [Tripwire docs](https://tripwirejs.com/docs) and [API reference](https://tripwirejs.com/docs/api-reference/introduction).

## Installation

You don't need this source code unless you want to modify the package. If you just want to use the package, run:

```bash
composer require abxy/tripwire-server
```

## Requirements

- PHP 8.1+

## Usage

The client can be created without a secret key for public or bearer-auth Gate flows. Secret-auth routes use `TRIPWIRE_SECRET_KEY` or `secretKey`:

```php
<?php

use Tripwire\Server\Client;

$client = new Client(secretKey: getenv('TRIPWIRE_SECRET_KEY') ?: null);

$page = $client->sessions()->list(verdict: 'bot', limit: 25);
$session = $client->sessions()->get('sid_0123456789abcdefghjkmnpqrs');

echo $session->decision['automation_status'] . ' ' . ($session->highlights[0]['summary'] ?? '') . PHP_EOL;
```

### Gate APIs

```php
<?php

use Tripwire\Server\Client;
use Tripwire\Server\GateDelivery;

$client = new Client();
$services = $client->gate()->registry()->list();
$session = $client->gate()->sessions()->create(
    serviceId: 'tripwire',
    accountName: 'my-project',
    delivery: GateDelivery::createDeliveryKeyPair()['delivery'],
);

echo $services[0]->id . ' ' . $session->consent_url . PHP_EOL;
```

### Sealed token verification

```php
<?php

use Tripwire\Server\SealedToken;

$result = SealedToken::safeVerify($sealedToken, getenv('TRIPWIRE_SECRET_KEY') ?: null);

if (!$result->ok) {
    error_log($result->error?->getMessage() ?? 'Tripwire verification failed.');
    return;
}

echo $result->data?->decision['verdict'] . ' ' . $result->data?->decision['risk_score'];
```

### Gate delivery and webhook helpers

```php
<?php

use Tripwire\Server\GateDelivery;

$keyPair = GateDelivery::createDeliveryKeyPair();
$response = GateDelivery::createGateApprovedWebhookResponse([
    'delivery' => $keyPair['delivery'],
    'outputs' => [
        'TRIPWIRE_PUBLISHABLE_KEY' => 'pk_live_...',
        'TRIPWIRE_SECRET_KEY' => 'sk_live_...',
    ],
]);
$payload = GateDelivery::decryptGateDeliveryEnvelope($keyPair['private_key'], $response['encrypted_delivery']);

echo $payload['outputs']['TRIPWIRE_SECRET_KEY'] . PHP_EOL;
```

### Pagination

```php
<?php

foreach ($client->sessions()->iterate(search: 'signup') as $session) {
    echo $session->id . ' ' . $session->latest_decision['verdict'] . PHP_EOL;
}
```

### Visitor fingerprints

```php
<?php

$fingerprint = $client->fingerprints()->get('vid_0123456789abcdefghjkmnpqrs');
echo $fingerprint->id;
```

### Teams

```php
<?php

$team = $client->teams()->get('team_0123456789abcdefghjkmnpqrs');
$updated = $client->teams()->update('team_0123456789abcdefghjkmnpqrs', name: 'New Name');

echo $updated->name;
```

### Team API keys

```php
<?php

$created = $client->teams()->apiKeys()->create('team_0123456789abcdefghjkmnpqrs', name: 'Production', environment: 'live');
$client->teams()->apiKeys()->revoke('team_0123456789abcdefghjkmnpqrs', $created->id);
```

### Error handling

```php
<?php

use Tripwire\Server\Exception\TripwireApiError;

try {
    $client->sessions()->list(limit: 999);
} catch (TripwireApiError $error) {
    error_log($error->status . ' ' . $error->code . ' ' . $error->getMessage());
}
```

## Support

If you need help integrating Tripwire, start with [tripwirejs.com/docs](https://tripwirejs.com/docs).
