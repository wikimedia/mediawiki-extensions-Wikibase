# 15) Use PSR-11-compatible MediaWiki service container as extension basis {#adr_0015}

Date: 2020-10-26

## Status

accepted

## Context

[WikibaseRepo](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Wikibase/+/62ae43e/repo/includes/WikibaseRepo.php) and [WikibaseClient](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Wikibase/+/62ae43e/client/includes/WikibaseClient.php) classes act as top level factories for services required inside of the respective extension.

The methods to create/retrieve them do not follow a standardized interface, and as many of them are required to build other services, the classes also hold a substantial amount of code to keep and pass references to the created instances once created. This code is low in conceptual value but highly repetitive and a burden on developers.

Services which are required in WikibaseRepo and WikibaseClient alike, e.g. [FormatterCacheFactory](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Wikibase/+/62ae43e/client/includes/WikibaseClient.php#1270), may also exhibit redundant implementations of their instantiator function as there is no ability to reuse them.

MediaWiki supports a concept called [ServiceWiring](https://www.mediawiki.org/w/index.php?title=Dependency_Injection&oldid=3977354#Quick_Start) which allows for the registration of services, is an important building block of recent MediaWiki and implements the well-known [PSR 11 container interface](https://www.php-fig.org/psr/psr-11/).

## Decision

We will use MediaWiki ServiceWiring to describe and connect the services on which the wikibase repo and client extensions rely.

## Consequences

The services described in the wikibase repo and wikibase client extensions will be migrated to respective service wiring files (e.g. `repo/WikibaseRepo.ServiceWiring.php`).

Boilerplate code, holding references to instances of services inside `WikibaseRepo` and `WikibaseClient`, can be removed.

The `WikibaseRepo` and `WikibaseClient` client classes will remain as type-safe accessors to the services governed by the service container ([as done in MediaWiki core](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/core/+/879c7fa/includes/MediaWikiServices.php#491)).

The ability to "make joint registration [of services] possible", as mentioned in [ADR #13](@ref adr_0013), is not immediately implemented, but the standardized interface eases future decisions in that direction.
