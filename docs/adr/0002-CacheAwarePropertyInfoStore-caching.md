# 2) CacheAwarePropertyInfoStore caching method  {#adr_0002}

Date: 2018-12-19

## Status

accepted

## Context

The [PropertyInfoStore] interfaces with the [wb_property_info] DB table holding information about wikibase properties.
The [CacheAwarePropertyInfoStore] stores the whole of the [wb_property_info]  table in a single cache key.
The Wikibase wiring is setup to provide this CacheAware store using the default cache (memcached for WMF).

The [CacheAwarePropertyInfoStore] has a high number of reads, and the method of storing the whole table in a single key
results in lots of traffic to a single memcached instance as described in [T97368].
The amount of traffic for the memcached key has steadily grown as the number of properties in the store have grown.
This traffic also moves between memcached servers after each WMF deploy as the cache key changes.

## Decision

A layer of APC caching (per server) is added on top of the shared memcached caching.
This is done in the service wiring by wrapping our [CacheAwarePropertyInfoStore] in another [CacheAwarePropertyInfoStore].
This on APC cache has a short TTL to avoid the need to actively think about purging.
Adding this extra layer of caching was chosen rather than anything more drastic as it is a trivial code change vs re-working how the [CacheAwarePropertyInfoStore] works.

## Consequences

Requests could end up with a slightly out of date [PropertyInfoStore] after a new property is created or updated, but
this would soon be fixed by the short TTL.

The ever increasing traffic for the individual memcached server has decreased.
https://phabricator.wikimedia.org/T97368#4741600

We will need to work again on this area and either:
 - Split the cache up per property
   - More requests to memcached, but less overall data retrieved
   - Much better distribution of keys between servers
 - Introduce more than 1 cache key for the same data to spread the traffic load
   - These cache keys could still end up on the same server
   - Duplicating this data more is evil
 - Increase the TTL for the on web server caching
   - This will slowly lead to increased time before all servers are in sync.

[T97368]: https://phabricator.wikimedia.org/T97368
[wb_property_info]: @ref docs_sql_wb_property_info
[PropertyInfoStore]: @ref Wikibase::Lib::Store::PropertyInfoStore
[CacheAwarePropertyInfoStore]: @ref Wikibase::Lib::Store::CacheAwarePropertyInfoStore
