![SeAT](http://i.imgur.com/aPPOxSK.png)
# eseye
👾 A Standalone, Dynamic ESI (EVE Swagger Interface) Client Library written in PHP

## example usage
Its supposed to be simple!

```php
// initialization stuff
$esi = new Eseye();

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
