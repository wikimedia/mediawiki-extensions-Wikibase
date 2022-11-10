- - -
Introduction to Wikibase
====================
[![Wikibase Secondary CI](https://github.com/wikimedia/Wikibase/actions/workflows/secondaryCI.yml/badge.svg)](https://github.com/wikimedia/Wikibase/actions/workflows/secondaryCI.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-Wikibase/badges/quality-score.png)](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-Wikibase/)

The Wikibase.git package is part of the [Wikibase software](http://wikiba.se/) and consists of
multiple MediaWiki extensions and other components.

The package allows for the creation, maintenance, dissemination, and usage of structured data
in MediaWiki.

High level documentation can be found on [wikiba.se](https://wikiba.se/) and [mediawiki.org](https://www.mediawiki.org/wiki/Wikibase).
Lower level documentation can be found on doc.wikimedia.org [here](https://doc.wikimedia.org/Wikibase/master/php/).

## Install

Wikibase depends on various [composer](http://getcomposer.org/) libraries.

Once you have Wikibase in your MediaWiki extensions directory, add the `composer.json` of Wikibase to `composer.local.json` at the root of your MediaWiki folder, as documented in [MediaWiki's Composer documentation](https://www.mediawiki.org/wiki/Composer#Using_composer-merge-plugin).

It should now look similar to:
```
{
  "extra": {
    "merge-plugin": {
       "include": [
         "extensions/Wikibase/composer.json"
       ]
    }
  }
}
```


Then, in the root of your MediaWiki folder, run:
```bash
composer install
```

If you already ran `composer install` during the installation of MediaWiki, run instead:
```bash
composer update
```


> When using ways to combine MediaWiki with the extension folders (e.g. symlinks or docker volumes) please make sure that the folders are available to composer in the same structure they are available to the webserver, too.

This will install both Wikibase Client and Repo together on the same wiki.

If you want to only have one or the other, then set `$wgEnableWikibaseRepo = false` or
`$wgEnableWikibaseClient` to false for the one you don't want to enable.

Wikibase also depends on several JavaScript libraries. They are included in this repository as submodules.
To fetch files of these libraries, you might need to run, in the Wikibase extension folder, the following command:
```bash
git submodule update --init
```

### Development

Wikibase uses tools to ensure the quality of software developed. To invoke these tools, inside the Wikibase folder, run

```bash
composer install
composer run-script test
```

> As this uses development dependencies and custom configuration, executing it from the MediaWiki root folder (via `composer run-script test extensions/Wikibase`) will not work satisfactorily

#### JavaScript

Wikibase makes use of frontend software from various eras - resulting in a heterogenous technological landscape.

Some notable (not a comprehensive list) mentions are
* the use of [ResourceLoader](https://www.mediawiki.org/wiki/ResourceLoader) to
  * allow for concatenation and minification of code neatly organized in separate files
  * translate less to CSS
  * model module inter-dependencies
  * handle delivery to the client through MediaWiki
* use of the [Javascript interfaces exposed by MediaWiki](https://www.mediawiki.org/wiki/Manual:Interface/JavaScript); e.g. `mw.hook` in [EntityInitializer](./repo/resources/wikibase.EntityInitializer.js)
* frontend components making heavy use of jQuery; e.g. for so called [experts](repo/resources/experts/Entity.js)
* the use of qunit to test this code; e.g. in `repo/tests/qunit`, available [via a special page](https://www.mediawiki.org/wiki/Manual:JavaScript_unit_testing)
* [vue.js](https://vuejs.org/guide/introduction.html) as a frontend framework; e.g. in [data-bridge](./client/data-bridge), the [Lexeme](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/WikibaseLexeme/) extension, and [termbox](https://gerrit.wikimedia.org/g/wikibase/termbox)


