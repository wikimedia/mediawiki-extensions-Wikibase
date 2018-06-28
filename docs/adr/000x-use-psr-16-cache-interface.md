# X. Use PSR-16 PHP Cache Interface in Wikibase

Date: 2018-06-28

## Status

proposed

## Context

Wikibase uses cache in different parts of PHP code base. Wikibase itself does provide any abstraction for cache implementation. Instead MediaWiki-specific classes, such as abstract `BagOStuff`, or `WANObjectCache`, are used in code.

[PSR-16 standard](https://www.php-fig.org/psr/psr-16/) defines an abstract Simple Cache interface for use in PHP code.

More formal and verbose Caching interface has been defined as [PSR-6](https://www.php-fig.org/psr/psr-6/). Using PSR-6 interface in MediaWiki has been proposed in 2016, but it has been declined (see: https://phabricator.wikimedia.org/T130528).

## Decision

We will introduce PSR-16-compliant cache interface to Wikibase. New PHP code using cache will use this abstraction, instead of binding directly to MediaWiki cache classes, or any other specific third-party implementations.

We will use [psr/simple-cache library](https://packagist.org/packages/psr/simple-cache) to add `CacheInterface` to Wikibase.

## Consequences

`psr/simple-cache` library will be added as a PHP dependency to Wikibase. This also means adding the library to `mediawiki-vendor` component of WMF.

PHP code will be hinting against the abstract PSR-16 `CacheInterface`. Existing MediaWiki's `BagOStuff` cache services could still be used. Some PSR-16-compliant implementation, or implementations, wrapping over `BagOStuff`, or `WANObjectCache`, might need to be added.

It will be possible to create code in Wikibase that uses cache, without needing to add dependency to MediaWiki.

It will be easy to replace real cache implementations with test doubles in Wikibase test code. In particular, there will be no need for tests to depend on MediaWiki only to be able to mock `BagOStuff` class.

Potential switching parts of Wikibase code to use different (e.g. non-MediaWiki's) cache implementation is going to be possible as long as there is PSR-16-compliant interface to the cache.
