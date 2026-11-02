# 3) Secondary statement store schema {#reuse_adr_0003}

Date: 2026-10-15

## Status

proposed

The work is paused since October 2026 ([T434870](https://phabricator.wikimedia.org/T434870)). This record is the state it resumes from.

## Context

The GraphQL `searchItems` field ([T403576](https://phabricator.wikimedia.org/T403576)) filters items by property and value. It is backed by WikibaseCirrusSearch and `haswbstatement`, which cannot give range queries or sorting by value ([T416170](https://phabricator.wikimedia.org/T416170) states the problem). In [Reuse ADR #2](@ref reuse_adr_0002), we decided to build a secondary statement store ([T433596](https://phabricator.wikimedia.org/T433596)) in MariaDB on the x3 cluster, derived from the entity revisions and rebuildable from them. A hoped-for side effect was reaching single statements without loading the whole item, which Reuse ADR #2 records as a possible additional benefit.

Range queries and sorting need each value in a column of its own type, so the schema has one value table per value type, not per data type: all string-typed data types share one table, local entity values another ([T434870#12384358](https://phabricator.wikimedia.org/T434870#12384358)). The first migration only needed item and string statements, but the schema was designed so that every data type fits, each further value type as a further table ([T434870#12270737](https://phabricator.wikimedia.org/T434870#12270737)).

Scope decisions taken during the design:

* Only best-ranked item statements are stored at first. Whether to also exclude scholarly articles and astronomical objects is undecided.
* Qualifiers and references are not stored. Faceted search filters on a statement's main property and value. A search that also filters on a qualifier needs a join whether qualifiers are stored in this table or in their own, so storing them here would not save it.
* Lookup by statement id is out of scope. Faceted search does not need it, and the DBA suggested loading the item instead ([T434870#12290920](https://phabricator.wikimedia.org/T434870#12290920)). The side effect above is therefore not served, which is what lets the statement GUID column go.
* Form and sense statements go to separate tables later, as the DBA suggested ([T434870#12273201](https://phabricator.wikimedia.org/T434870#12273201)); they have no numeric id for the subject column.
* Federation is deferred. A federated value is identified by its concept URI ([ADR #37](@ref adr_0037)), not a numeric id, so it cannot use the numeric value column. Two shapes were suggested: a separate table for external statements ([T434870#12266928](https://phabricator.wikimedia.org/T434870#12266928)) and a table mapping external entities to local negative ids ([T434870#12267705](https://phabricator.wikimedia.org/T434870#12267705)).

Before continuing the review, the DBA asked for seven numbers: total statements; statements per value type; distinct values per value type; deprecated statements; best-ranked statements; no-value or some-value statements; and the statements on scholarly articles and astronomical objects ([T434870#12311884](https://phabricator.wikimedia.org/T434870#12311884)). We counted them in the [census of the 2026-09-14 dump](https://analytics.wikimedia.org/published/reports/wmde/wikidata-statement-count/wikidata-statement-count-2026-09-14.html) ([T437880](https://phabricator.wikimedia.org/T437880)); the numbers in this record come from it.

## How the proposal evolved

The schema went through three states, each a revision of the same proposal after review. The task description ([T434870](https://phabricator.wikimedia.org/T434870)) was rewritten at each step, so the reasoning behind each change is recorded here. The first two states can be seen in the patch set history of [Gerrit 1328175](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1328175).

### First draft (patch sets [2](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1328175/2), [3](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1328175/3) and [4](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1328175/4))

The shape from the team's design session:

* Statement GUID as primary key; subject and property ids as strings.
* A revision id per row, so an update can tell which revision a row came from.
* Rank and best-ness as columns, repeated in the value tables so value-side lookups need no join.
* One value table per value type, keyed by statement.

### DBA revision ([patch set 5](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1328175/5), [T434870](https://phabricator.wikimedia.org/T434870) description)

After the DBA's preliminary review ([T434870#12266928](https://phabricator.wikimedia.org/T434870#12266928)): the GUID key and the string ids made the tables too large per host, and the design should start from the queries rather than from the data model.

* Integer surrogate keys; numeric subject and property ids with an entity type column beside the subject id.
* Entity values interned as property and value pairs in one table, referenced by id; string values as a separate table.
* No revision id. On each edit, the subject's existing rows are read from a replica, compared with the statements of the new revision, and only the difference is written, the way the links tables are updated ([T434870#12290920](https://phabricator.wikimedia.org/T434870#12290920)).
* The GUID kept as a plain unindexed column; rank and best-ness only on the main table.

### After the census (no patch set)

After the DBA's later comments ([T434870#12311884](https://phabricator.wikimedia.org/T434870#12311884)) and the census numbers: each distinct item value appears in about 21 statements and each distinct string in about 2, and deprecated, non-best and no-value or some-value statements are too rare for a column each.

* Rank, best-ness and no-value or some-value marked by side tables rather than by columns. The main table holds the common case, and only the queries that ask for the rare cases read a side table.
* The GUID column dropped; lookup by statement id is not served.

## Decision

Proposed, not decided. The work was paused before the DBA review concluded. The proposal is the state after the census: the DBA revision ([patch set 5](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1328175/5)) with the two changes above applied, which is where the team would have continued.

Taken as given for any resumption:

* integer keys and numeric ids, sizes judged per host on x3;
* interned entity values; other value types deduplicated only where the duplication is high;
* no per-row revision id; updates by comparing with a replica;
* rare cases in side tables, never as columns on the main table;
* best-ranked item statements only as the initial subset.

## DBA advice

The WMF DBA's positions over the review:

* Plan for about 2 billion rows, judged per host (19 hosts on x3); no GUID key, no string ids.
* Intern property and value pairs; joins are cheap because the hosts have spare CPU and scarce memory.
* No column for a rarely true case such as deprecated rank; a side table read only by the queries that need it.
* No revision id; update by reading existing rows from a replica, as the links tables do.
* Drop the GUID; it costs about 120 GB and does not compress.
* Federation is not a feature this store can support; at most a separate table for external statements.
* Narrow the scope: find items that have certain statements, load the rest through the caches, leave complex queries to the query service.
* Numbers before design: the seven metrics above, answered by the census.
* Backfill: the `wb_terms` normalization took three to six months; x3 writes should be faster.

## Open questions

1. **Scope and the index set.** The DBA's advice to narrow the scope stands against value-only queries (by value, with any property) and combined ones; the team's use case needs them, with range queries and sorting by value. Resolving it decides whether a value-leading index is needed and whether it can be added later on two billion rows, which the task asked the DBA and was never answered.

## Consequences

* The store answers one question: which items have a given property and value, over best-ranked item statements. Lookup by statement id, qualifiers, references, form and sense statements and federated values are not served, and a REST statement endpoint cannot be backed by it.
* Deprecated, non-best and no-value or some-value statements are marked by side tables. The default query reads the main table; the rarer cases cost a second table and a second query path, and each new case is a new table.
* Updates compare against a replica per subject. With no revision id per row, correctness under replication lag rests on that comparison, and drift is repaired by rebuild, as with the other secondary stores.
* Every value type is a table. Quantity and time have none yet; adding one means a new table and index set on populated data, and whether indexes can be added later is the open question.
* A backfill of roughly 1.7 billion rows on x3, at a rate nobody has estimated; the nearest precedent took months.
* Excluding scholarly articles and astronomical objects would change GraphQL results; whether to do it is a product decision this proposal does not make.
* [Gerrit 1328175](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1328175) is not merged as is; it needs a new patch set applying the census changes first.
