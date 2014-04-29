# Wikibase.git

[![Build Status](https://secure.travis-ci.org/wikimedia/mediawiki-extensions-Wikibase.png?branch=master)](http://travis-ci.org/wikimedia/mediawiki-extensions-Wikibase)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-Wikibase/badges/quality-score.png?s=857e6982ca67c05cd28ce53ba6d42e7e20b89325)](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-Wikibase/)

The Wikibase.git package is part of the [Wikibase software](http://wikiba.se/) and consists of
three interconnected extensions:

* Wikibase Repository (in the directory repo)
* Wikibase Client (in the directory client)
* WikibaseLib (in the directory lib)

These extensions allow for the creation, maintenance, dissemination, and usage of structured data
in MediaWiki.

The repo is the extension for the repository. It allows the creation and maintenance of structured
data. This is being used on [wikidata.org](https://www.wikidata.org)  .

The client is the extension for the client. It allows several MediaWiki instances to use data provided
by a Wikidata instance. Usually, you would not use them in a single wiki. This is being used on the
Wikipedias.

The lib bundles common code that is used by both the client and the repo.

Note that in each of the directories you will also find `README.md` notes for each of the extensions.

## Install

This package contains three interrelated MediaWiki extensions:

* Wikibase (in the subdirectory repo)
* WikibaseLib (in the subdirectory lib)
* Wikibase Client (in the subdirectory client)

In order to enable experimental features for the extensions, put the below line before the inclusion
of the extensions in your LocalSettings.php file:

```php
define( 'WB_EXPERIMENTAL_FEATURES', true );
```

Wikibase depends on various libraries such as [DataValues](https://github.com/DataValues/) components,
and uses [Composer](http://getcomposer.org/) to make it easy to install and manage those.

Once you have Wikibase in your MediaWiki extensions directory, then run:

```bash
composer install
```

This will install both Wikibase Client and Repo together on the same wiki.

If you want to only have one or the other, then set `$wgEnableWikibaseRepo = false` or
`$wgEnableWikibaseClient` to false for the one you don't want to enable.

## The Wikibase software

These extensions are part of the [Wikibase software](http://wikiba.se/) created by the Wikidata team
for the [Wikidata project](https://meta.wikimedia.org/wiki/Wikidata).

The Wikidata project uses the Wikibase software on its [wikidata.org website]
(https://www.wikidata.org).
