![SeAT](http://i.imgur.com/aPPOxSK.png)
# eseye

## This repository contains Eseye.
For a usage example, please refer to [example.php](example.php)

## example usage
Using the library should be really simple. Essentially, all you want to do is instantiate a new `Eseye` instance, provide the authentication information with a `EsiAuthentication` container, and make calls with `invoke`!

Lets look at a shortened example. For a complete example, have a look at the script included [here](example.php):

```php
include 'vendor/autoload.php';

use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;


// Prepare an authentication container for ESI
$authentication = new EsiAuthentication([
    'client_id'     => 'SSO_CLIENT_ID',
    'secret'        => 'SSO_SECRET',
    'refresh_token' => 'CHARACTER_REFRESH_TOKEN',
]);

// Instantiate a new ESI instance.
$esi = new Eseye($authentication);

$clones = $esi->invoke('get', '/characters/{character_id}/clones/', [
    'character_id' => 1477919642,
]);

echo 'You have the following clones: ' . PHP_EOL;
foreach ($clones->jump_clones as $jump_clone) {

    echo 'Clone at a ' . $jump_clone->location_type .
        ' with ' . count($jump_clone->implants) . ' implants' . PHP_EOL;
}

```
