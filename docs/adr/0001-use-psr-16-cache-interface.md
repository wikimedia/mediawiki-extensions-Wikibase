# 1) Use PSR-16 PHP Cache Interface in Wikibase {#adr_0001}

Date: 2018-07-19

## Status

accepted

## Context

Wikibase uses cache in different parts of PHP code base. Wikibase itself does not provide any abstraction for cache implementation. Instead MediaWiki-specific classes, such as abstract `BagOStuff`, or `WANObjectCache`, are used in code.

The [PSR-16] standard defines an abstract Simple Cache interface for use in PHP code.

More formal and verbose Caching interface has been defined as [PSR-6].
We find it overly verbose compared to simpler [PSR-16], which we favour here.

Possible use of [PSR-16] in MediaWiki is out of scope of this decision.
It might be a possible next step.
In this regard it is possibly worth mentioning that using [PSR-6] interface in MediaWiki has been proposed in 2016, but it has been declined (see: [T130528]).
It should also be noted that there is already a PSR-6 adapter for the `BagOStuff`: https://packagist.org/packages/addshore/psr-6-mediawiki-bagostuff-adapter.
There are already adapters that can use a PSR-16 cache as a PSR-6 cache and vice versa: https://symfony.com/doc/current/components/cache/psr6_psr16_adapters.html.
Also, as `Psr\Cache\CacheItemPoolInterface` and `Psr\SimpleCache\CacheInterface` declare only one method of the same name, `clear()`, and as both declarations have the same signature, any cache system may implement both interfaces.

## Decision

We will introduce PSR-16-compliant cache interface to Wikibase.
New PHP code using cache will use this abstraction, instead of binding directly to MediaWiki cache classes, or any other specific third-party implementations.

We will use [psr/simple-cache] library to add `CacheInterface` to Wikibase.

## Consequences

[psr/simple-cache] library will be added as a PHP dependency to Wikibase.
This also means adding the library to `mediawiki-vendor` component of WMF.

PHP code will be hinting against the abstract [PSR-16] `CacheInterface`.
Existing MediaWiki's `BagOStuff` cache services could still be used.
Some [PSR-16]-compliant implementation, or implementations, wrapping over `BagOStuff`, or `WANObjectCache`, will be added.

It will be possible to create code in Wikibase that uses cache, without needing to add dependency to MediaWiki.

It will be easy to replace real cache implementations with test doubles in Wikibase test code.
In particular, there will be no need for tests to depend on MediaWiki only to be able to mock `BagOStuff` class.

Potential switching parts of Wikibase code to use different (e.g. non-MediaWiki's) cache implementation is going to be possible as long as there is PSR-16-compliant interface to the cache.

[mediawiki-vendor]: https://gerrit.wikimedia.org/g/mediawiki/vendor
[T130528]: https://phabricator.wikimedia.org/T130528
[psr/simple-cache]: https://packagist.org/packages/psr/simple-cache
[PSR-16]: https://www.php-fig.org/psr/psr-16
[PSR-6]: https://www.php-fig.org/psr/psr-6
