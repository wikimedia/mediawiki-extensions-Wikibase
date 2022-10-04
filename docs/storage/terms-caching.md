# Item & Property Terms Caching

Item and Property terms are frequently requested bits of information. To take load off of the [secondary terms storage] we apply caching in various places.

With the migration to the new SQL database schema for storing terms, caching became even more important. The normalization of the old table allows for a clearer structure with less redundant data, but comes at the cost of distributing the data across more tables, thus making queries more complex and more expensive.

## Term Cache Containing Language Fallbacks

The so-called "TermFallback Cache" contains serialized [TermFallback] objects as its values, which contain the requested languages, as well as the actual language of the [Term] object in case of a fallback. The intended main use case is to provide terms for formatted statement values (i.e. mainly Item terms) via services such as [CachingFallbackLabelDescriptionLookup].
It is also used in the corresponding Lua code for term lookups. It is used both for those with and without fallbacks to reduce load.

It should be accessed using the TermFallbackCacheFacade.

This cache consists of the wiki's [configured shared cache] (shared across sites) wrapped in an in-process cache, which can be seen in [WikibaseRepo::getTermFallbackCache()] and [WikibaseClient::getTermFallbackCache()]. The shared cache provides consistent cache contents across connected wikis and reduces load on the database, while the outer in-memory cache avoids expensive repeated lookups within the same request.

This used to be called the formatter cache. The cache key (and metrics collection) still use this string: `wikibase.repo.formatter`.

## Property Term Cache (without Language Fallbacks)

This cache holds the string value of the term, or `false` if there is no term in the requested language. This cache is wired up for use with [CachingPrefetchingTermLookup], and is only used with Property terms for now.

The contents are stored in a local server cache as configured in [WikibaseLib.entitytypes.php]. As Property terms are a comparatively small dataset they're cheap to replicate across multiple application servers. They're also looked up more frequently than Item terms and so storing them locally is preferable due to faster response times and reduction in network overhead.

## Immutable Cache Entries

Cache entries are considered immutable which means that they don't change once they're created. To make this possible cache keys must therefore contain the revision ID alongside the entity ID, language, and term type of the stored term value. A [TermCacheKeyBuilder] utility trait exists for the creation of such keys.

[secondary terms storage]: @ref docs_storage_terms
[configured shared cache]: @ref common_sharedCacheType
[CachingFallbackLabelDescriptionLookup]: @ref Wikibase::Lib::Store::CachingFallbackLabelDescriptionLookup
[CachingPrefetchingTermLookup]: @ref Wikibase::Lib::Store::CachingPrefetchingTermLookup
[TermCacheKeyBuilder]: @ref Wikibase::Lib::Store::TermCacheKeyBuilder
[TermFallback]: @ref Wikibase::DataModel::Term::TermFallback
[Term]: @ref Wikibase::DataModel::Term::Term
[WikibaseClient::getTermFallbackCache()]: @ref Wikibase::Client::WikibaseClient::getTermFallbackCache()
[WikibaseRepo::getTermFallbackCache()]: @ref Wikibase::Repo::WikibaseRepo::getTermFallbackCache()
[WikibaseLib.entitytypes.php]: @ref WikibaseLib.entitytypes.php
