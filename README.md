![SeAT](http://i.imgur.com/aPPOxSK.png)
# eseye

## This repository contains Eseye.
For a usage example, please refer to [example.php](example.php)

## example usage
Using the library should be really simple. Essentially, all you want to do is instantiate a new `Eseye` instance, provide the authentication information with a `EsiAuthentication` container, and make calls with `invoke`!

Lets look at the example script included [here](example.php):

```
include 'vendor/autoload.php';
```

As this is a composer package, you will have to install that first and include the generated autoloader.

```
use Seat\Eseye\{
    Containers\EsiAuthentication, Eseye
};
```

Next, we just include the imports needed for the classes used in the script.

```
// Prepare an authentication container for ESI
$authentication = new EsiAuthentication([
    'client_id'     => 'SSO_CLIENT_ID',
    'secret'        => 'SSO_SECRET',
    'access_token'  => 'CHARACTER_ACCESS_TOKEN',
    'refresh_token' => 'CHARACTER_REFRESH_TOKEN',
]);

// Instantiate a new ESI instance.
$esi = new Eseye($authentication);
```

This section defines the authentication information for the ESI session. The client_id and secret is sourced from your application that you have registered with CCP. The access_token and refresh_token comes from a completed OAUTH authentication flow callback that was made.

`$esi` is then Instantiated with that authentication information an an argument. It is also possible to instantiate a new `Eseye` instance in one line as follows:

```
$esi = new Eseye(new EsiAuthentication([
    'client_id'     => 'SSO_CLIENT_ID',
    'secret'        => 'SSO_SECRET',
    'access_token'  => 'CHARACTER_ACCESS_TOKEN',
    'refresh_token' => 'CHARACTER_REFRESH_TOKEN',
]));
```

The choice is yours.

```
// Get character information. This is a public call to the EVE
// Swagger Interface
$character_info = $esi->invoke('get', '/characters/{character_id}/', [
    'character_id' => 1477919642,
]);
```

This section finally makes a call to ESI. Notice how the method and route is a literal copy and paste from the ESI swagger page [here](https://esi.tech.ccp.is/latest/#!/Character/get_characters_character_id). Fields in curly braces are populated from the array passed as a second argument to `invoke` which is `character_id` in this case.

```
// Get the location information for a character. This is an authenticated
// call to the EVE Swagger Interface.
$location = $esi->invoke('get', '/characters/{character_id}/location/', [
    'character_id' => 1477919642,
]);
```

Similarly, the location information is again a copy and paste from the route defined [here](https://esi.tech.ccp.is/latest/#!/Location/get_characters_character_id_location). This call is authenticated, so `Eseye` will first make sure the associated `access_token` has the required scope to make this call.

```
// Print some information from the calls we have made.
echo 'Character Name is:        ' . $character_info->name . PHP_EOL;
echo 'Character was born:       ' . carbon($character_info->birthday)
        ->diffForHumans() . PHP_EOL;    // The 'carbon' helper is included in the package.
echo 'Home Solar System ID is:  ' . $location->solar_system_id . PHP_EOL;
echo 'Home Station ID is:       ' . $location->station_id . PHP_EOL;
```

With all of the information ready, we can finally access the fields returned and print some information!
