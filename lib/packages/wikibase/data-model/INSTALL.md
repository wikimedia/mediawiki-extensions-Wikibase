Installation of Wikibase DataModel
====================================

Wikibase DataModel has the following dependencies:

* [DataValues](https://www.mediawiki.org/wiki/Extension:DataValues) 0.1 or later
* [Diff](https://www.mediawiki.org/wiki/Extension:Diff) 0.6 or later

And nothing else.

It also requires PHP 5.3 or above to run.

Installation with Composer
--------------------------

The standard and recommended way to install Wikibase DataModel is with [Composer](http://getcomposer.org).
If you do not have Composer yet, you first need to install it, or get the composer.phar file.

Depending on your situation, pick one of the following 3 approaches:

1. If you already have a copy of the Wikibase DataModel code, change into its root
directory and type "composer install". This will install all dependencies of Wikibase DataModel.

2. If you want to get Wikibase DataModel and all of its dependencies, use
"composer create-package wikibase/data-model".

3. If you have a component (ie a MediaWiki extension) into which you want to install
Wikibase DataModel, then add the "wikibase/data-model" package in the "require" section
of the composer.json of your component and run "composer install".

For more information on using Composer, see [using composer](http://getcomposer.org/doc/01-basic-usage.md).

The entry point of Wikibase DataModel is WikibaseDataModel.php. Including this file
takes care of autoloading and defining the version constant of this component.

Installation without composer
-----------------------------

If you install without composer, simply include the entry point file. You are then
responsible for loading all dependencies of this component before including the
entry point, and can do this in whatever way you see fit.

Usage as MediaWiki extension
-----------------------------

Though Wikibase DataModel is a PHP library that has no knowledge of MediaWiki,
its entry point contains some code that will register it with MediaWiki as extension
in case MediaWiki is loaded. This allows one to see the version of Wikibase DataModel
when it is used by an actual MediaWiki extension.

You can install either with Composer or without.
Installation is not affected by MediaWiki usage.