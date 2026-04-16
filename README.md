# coyote-cert — ACME v2 certificate client for PHP

This library allows you to request, renew and revoke SSL certificates provided by Let's Encrypt via the ACME v2 protocol.

## Requirements
- PHP ^8.3
- OpenSSL >= 1.0.1
- cURL extension
- JSON extension

**Notes:**
* It's recommended to have [dig](https://linux.die.net/man/1/dig) installed on your system, as it will be used to fetch DNS information.

## Installation
```
composer require blendbyte/coyote-cert
```

## Usage

Create an instance of `CoyoteCert\Api` and provide it with a local account that will be used to store the account keys.

```php
$localAccount = new \CoyoteCert\Support\LocalFileAccount(__DIR__.'/__account');
$client = new Api(localAccount: $localAccount);
```

You could also create a client and pass the local account later:

```php
$client = new Api();

// Do some stuff.

$localAccount = new \CoyoteCert\Support\LocalFileAccount(__DIR__.'/__account');
$client->setLocalAccount($localAccount);
```

> Please note that **setting a local account is required** before making any of the calls detailed below.

### Creating an account
```php
if (! $client->account()->exists()) {
    $account = $client->account()->create();
}

// Or get an existing account.
$account = $client->account()->get();
```

### Difference between `account` and `localAccount`
- `account` is the account created at the ACME (Let's Encrypt) server with data from the `localAccount`.
- `localAccount` handles the private/public key pair used to sign requests to the ACME server. Depending on the implementation, this data is stored locally or, for example, in a database.

### Creating an order
```php
$order = $client->order()->new($account, ['example.com']);
```

#### Renewal
Simply create a new order to renew an existing certificate as described above. Ensure that you use the same account as you did for the initial request.

#### Getting an order
```php
$order = $client->order()->get($order->id);
```

### Domain validation

#### Getting the DCV status
```php
$validationStatus = $client->domainValidation()->status($order);
```

#### http-01

Get the name and content for the validation file:
```php
$validationData = $client->domainValidation()->getValidationData($validationStatus, \CoyoteCert\Enums\AuthorizationChallengeEnum::HTTP);
```

This returns an array:
```php
Array
(
    [0] => Array
        (
            [type] => http-01
            [identifier] => example.com
            [filename] => sqQnDYNNywpkwuHeU4b4FTPI2mwSrDF13ti08YFMm9M
            [content] => sqQnDYNNywpkwuHeU4b4FTPI2mwSrDF13ti08YFMm9M.kB7_eWSDdG3aWIaPSp6Uy4vLBbBI5M0COvM-AZOBcoQ
        )
)
```

The Let's Encrypt validation server will make a request to:
```
http://example.com/.well-known/acme-challenge/sqQnDYNNywpkwuHeU4b4FTPI2mwSrDF13ti08YFMm9M
```

#### dns-01

Get the name and value for the TXT record:
```php
$validationData = $client->domainValidation()->getValidationData($validationStatus, \CoyoteCert\Enums\AuthorizationChallengeEnum::DNS);
```

This returns an array:
```php
Array
(
    [0] => Array
        (
            [type] => dns-01
            [identifier] => example.com
            [name] => _acme-challenge
            [value] => 8hSNdxGNkx4MI7ZN5F8uZj3cTSMX92SGMCMHQMh0cMA
        )
)
```

#### Start domain validation

##### http-01
```php
try {
    $client->domainValidation()->start($account, $validationStatus[0], \CoyoteCert\Enums\AuthorizationChallengeEnum::HTTP);
} catch (DomainValidationException $exception) {
    // The local HTTP challenge test has failed...
}
```

##### dns-01
```php
try {
    $client->domainValidation()->start($account, $validationStatus[0], \CoyoteCert\Enums\AuthorizationChallengeEnum::DNS);
} catch (DomainValidationException $exception) {
    // The local DNS challenge test has failed...
}
```

#### Generating a CSR
```php
$privateKey = \CoyoteCert\Support\OpenSsl::generatePrivateKey(key_type: OPENSSL_KEYTYPE_RSA);
// ^- switch to "key_type: OPENSSL_KEYTYPE_EC" to generate an ECDSA key and certificate instead
$csr = \CoyoteCert\Support\OpenSsl::generateCsr(['example.com'], $privateKey);
```

#### Finalizing order
```php
if ($order->isReady() && $client->domainValidation()->allChallengesPassed($order)) {
    $client->order()->finalize($order, $csr);
}
```

#### Getting the certificate
```php
if ($order->isFinalized()) {
    $certificateBundle = $client->certificate()->getBundle($order);
}
```

#### Revoke a certificate
```php
if ($order->isValid()) {
    $client->certificate()->revoke($certificateBundle->fullchain);
}
```
