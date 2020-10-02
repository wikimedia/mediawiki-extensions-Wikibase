# 0) Use cache instead of wb_terms for efficient fetching of data needed to display an entity in the short form {#adr_0000}

Date: 2018-06-28

## Status

accepted

## Context

When an item or a property is displayed in the short form, either as a link, or as a simple text reference, data needed to render this short form are currently loaded from the SQL table (wb_terms). wb_terms is causing several significant issues, and is not possible to be maintained any more in the long run.

Decision to use wb_terms, initially introduced as a SQL search index, has been tracked down to [change 176650](https://gerrit.wikimedia.org/r/#/c/mediawiki/extensions/Wikibase/+/176650/). As discussed there in the code review, and also in https://phabricator.wikimedia.org/T74309#798908, it seems there had been no dramatic performance improvements expected, neither noticed after switching to use wb_terms instead of loading the data of the entire item or property.

Wikibase already uses MediaWiki's caching mechanisms (in production Wikidata environment being based on memcached) to reduce loading of full entity data.

In case of lexemes or forms, entity types provided by WikibaseLexeme extension, that have different internal structure than items and properties, wb_terms has not been used as a source of data for short form display. Full lexeme data has been loaded instead. Early tests didn't show significant performance issues (see https://phabricator.wikimedia.org/T188108). Also, due to different internal structure of lexemes, or forms, and the way how their "short form" displayed is built, the possible use of wb_terms has not even seem feasible without changing the semantics of the table.

## Decision

As long as using SQL table as a storage of the data used for displaying entities in the short form does not bring significant performance gains, we decide to stop using wb_terms as a data source for this use case.

Instead, data of the whole entity is going to be retrieved from storage layer (from the database, or from cached storage that are already in place).

If not efficient enough (e.g. in case of huge-size Wikibase instances like Wikidata ), data needed for display will also be stored in cache, e.g. label of an item in a particular language. That should reduce the amount of computation needed, especially when language fallback needs to be applied, etc.

## Consequences

As there is no reliable benchmark in place, it is possible that the new way of displaying entities performs worse . In worst case, switching back to wb_terms-solution would be a quick fix that could be easily applied in case of serious problems.

Increased use of memcached in Wikidata production environment might impact the performance of the caching system, e.g. in case there is huge amount of entries being added to cache because of the new approach.

Use the cache is going to be to used for storing the data that is changing (edited), possible cache invalidation mechanism should be considered. This is left out for the more specific ADR document.

## Appendix

The `wb_terms` table has been removed in 2020.

It used to look like this:

```
+---------------------+---------------------+------+-----+---------+----------------+
| Field               | Type                | Null | Key | Default | Extra          |
+---------------------+---------------------+------+-----+---------+----------------+
| term_row_id         | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| term_entity_id      | int(10) unsigned    | NO   | MUL | NULL    |                |
| term_full_entity_id | varbinary(32)       | YES  | MUL | NULL    |                |
| term_entity_type    | varbinary(32)       | NO   |     | NULL    |                |
| term_language       | varbinary(32)       | NO   | MUL | NULL    |                |
| term_type           | varbinary(32)       | NO   |     | NULL    |                |
| term_text           | varbinary(255)      | NO   | MUL | NULL    |                |
| term_search_key     | varbinary(255)      | NO   | MUL | NULL    |                |
| term_weight         | float unsigned      | NO   |     | 0       |                |
+---------------------+---------------------+------+-----+---------+----------------+
```
