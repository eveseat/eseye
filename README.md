![SeAT](http://i.imgur.com/aPPOxSK.png)

[![Build Status](https://travis-ci.org/eveseat/eseye.svg?branch=master)](https://travis-ci.org/eveseat/eseye)
[![Code Climate](https://codeclimate.com/github/eveseat/eseye/badges/gpa.svg)](https://codeclimate.com/github/eveseat/eseye)
[![Test Coverage](https://codeclimate.com/github/eveseat/eseye/badges/coverage.svg)](https://codeclimate.com/github/eveseat/eseye/coverage)
[![Latest Stable Version](https://poser.pugx.org/eveseat/eseye/v/stable)](https://packagist.org/packages/eveseat/eseye)
[![Total Downloads](https://poser.pugx.org/eveseat/eseye/downloads)](https://packagist.org/packages/eveseat/eseye)
[![Latest Unstable Version](https://poser.pugx.org/eveseat/eseye/v/unstable)](https://packagist.org/packages/eveseat/eseye)
[![License](https://poser.pugx.org/eveseat/eseye/license)](https://packagist.org/packages/eseye/eveapi)
[![StyleCI](https://styleci.io/repos/78866259/shield?branch=master)](https://styleci.io/repos/78866259)

# eseye
👾 A Standalone, Dynamic ESI (EVE Swagger Interface) Client Library written in PHP

## example usage
Its supposed to be simple!

```php
// initialization stuff
$esi = new Eseye();

// Optionally, set the ESI endpoint version to use.
// If you dont set this, Eseye will use /latest
$esi->setVersion('v4');

// make a call
$character_info = $esi->invoke('get', '/characters/{character_id}/', [
    'character_id' => 1477919642,
]);

// get data!
echo $character_info->name;
```

For a more complete usage example, please refer to [example.php](example.php)

## documentation
For up to date documentation, more examples and other goodies, please check out the [project wiki](https://github.com/eveseat/eseye/wiki)!
