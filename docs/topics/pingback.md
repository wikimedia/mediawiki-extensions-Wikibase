# Pingback mechanism

## WikibasePingback

Wikibase uses a pingback mechanism to anonymously report information about the mediawiki installation it's running on. This information is very important to get a better understanding of how Wikibase is being used and will help to inform the decision making of the development process.

The pingback will be sent once Wikibase has been installed and will after that send the same pingback every month until turned off.

## Configuration

By default the pingback mechanism is disabled on new installtions. To enable the pingback set the `wikibasePingback` setting to true for your repository.

```php
$wgWBRepoSettings['wikibasePingback'] = true;
```

## Example

To view what data is included in the pingback, the following example can be run from the mediawiki shell.

```sh
root@mediawiki:/var/www/mediawiki# php maintenance/shell.php
>>> (new Wikibase\Repo\WikibasePingback())->getSystemInfo();
=> [
     "database" => "mysql",
     "mediawiki" => "1.36.0-alpha",
     "hasEntities" => false,
     "federation" => true,
     "extensions" => [
       "ULS",
       "WBL",
       "PS",
       "BBL",
       "WBQC",
       "WBCS",
       "WBMI",
       "CLDR",
     ],
     "termbox" => true,
   ]
```

[WikibasePingback]: @ref Wikibase::Repo::WikibasePingback
