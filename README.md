# ScreenLab API Client

A simple client for screenlab.io API.

## Requirements

- PHP 5.6+
- guzzlehttp/guzzle 6.2+

## Installing

Use Composer to install it:

```
composer require filippo-toso/screen-lab
```

## Using It

```
use FilippoToso\ScreenLab\Client as ScreenLab;

$client = new ScreenLab('username', 'password', 'access_token');

```

Get the access token from the API
```
$access_token = $client->getAccessToken($email, $password);
```

Refreshes the access token for the provided user
```
$access_token = $client->refreshAccessToken($email, $password);
```

Generate a scan of the provided URL

```
$job = $client ->generateScan($url, $name, $width, $height);
```

Get a single scan by ID
```
$scan = $client->getScan($id);
```

Get the list of all scans
```
$scans = $client->getScans();
```
