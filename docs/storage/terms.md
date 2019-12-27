# Item & Property Terms

Secondary storage for Item and Property terms in SQL is needed for efficient and atomic lookup and query of the terms of multiple entities in multiple languages.

For example, when rendering an Item page the labels of all other entities being referred to need to be known.
The alternative to secondary storage would be loading each of the full entities in order to lookup the terms needed.

The code for the storage lives in the [Wikibase\Lib\Store\Sql\Terms] namespace.

Briefly in code:
 - ItemTermStore and PropertyTermStore are the interfaces at the bottom of the term storage tree.
   - These interfaces are provided by the `term-store` [vendor component]
   - Implementations exist to write to the new and legacy storage as well as other implementations allowing mixed reading and writing.
 - [EntityTermStoreWriter] joins these stores in an interface that can generically save either Item or Property terms.
 - [EntityHandler] takes a [EntityTermStoreWriter] which is used in a few data updates relating to saving and deleting entities.

The storage system is currently decided using the `tmpItemTermsMigrationStages` and `tmpPropertyTermsMigrationStages` repo settings.

### Legacy Secondary Storage

This currently the default storage mechanism when using Wikibase.

In the past (pre 2020) terms were stored in a single large database table called wb_terms.
This table lacked clear design and eventually became too big to touch for wikidata.org.
Between 2019 and 2020 a migration process was carried out (and is still being carried out) migrating the terms to a new schema (see below).

The "Epic" task for this was https://phabricator.wikimedia.org/T208425 - *[EPIC] Kill the wb_terms table*

### New Secondary Storage

The storage is made up of multiple normalized tables, all prefixed with "wbt_".DatabaseTermIdsAcquirer
The tables were created by [AddNormalizedTermsTablesDDL.sql] which includes some documentation.

* [wbt_item_terms]
* [wbt_property_terms]
* [wbt_term_in_lang]
* [wbt_text_in_lang]
* [wbt_type]
* [wbt_text]

The relations are shown below:

\dot
digraph models_diagram{
    graph[rankdir=RL, overlap=false];
    node [shape=record];
  wbt_term_in_lang -> wbt_item_terms [arrowhead="crow"]
  wbt_term_in_lang -> wbt_property_terms [arrowhead="crow"]
  wbt_text_in_lang -> wbt_term_in_lang [arrowhead="crow"]
  wbt_type -> wbt_term_in_lang [arrowhead="crow"]
  wbt_text -> wbt_text_in_lang [arrowhead="crow"]

}
\enddot

The Normalization results in a more complex query and update pattern.
See sections below for more details on how Reading and Updating work.

#### Read queries

* Lookup of the terms of an entity can be achieved by starting with the [wbt_item_terms] or [wbt_property_terms] tables where you will find integer representations of Item and Property identifiers.
* Lookup of entities from a term string can be achieved by starting with the [wbt_text] table which contains the text for all terms or all types for both Items and Properties.

Checking data for these tables involves lots of joins.
The below query selects all terms in the tables for item Q123 and can be used as a starting point for data exploration:

```sql
SELECT
  wbit_item_id as id,
  wby_name as type,
  wbxl_language as language,
  wbx_text as text
FROM wbt_item_terms
LEFT JOIN wbt_term_in_lang ON wbit_term_in_lang_id = wbtl_id
LEFT JOIN wbt_type ON wbtl_type_id = wby_id
LEFT JOIN wbt_text_in_lang ON wbtl_text_in_lang_id = wbxl_id
LEFT JOIN wbt_text ON wbxl_text_id = wbx_id
WHERE wbit_item_id = 123;
```

In order to query properties change:
 - wbit_item_id -> wbpt_property_id (in 2 places)
 - wbt_item_terms -> wbt_property_terms

#### Updating

Actual insertion and deletion of the terms in the `wbt_item_terms` and `wbt_property_terms` tables is done in [DatabaseItemTermStore] and [DatabasePropertyTermStore].

 - When term changes happen a series of ID acquisitions occur in [DatabaseTermIdsAcquirer]. (Finding IDs that already exist in the storage that will be needed for future inserts)
 - When new terms are being introduced rows that do not appear in the acquisition will be inserted.
 - When term changes result in some terms no longer being used across the whole store they will be deleted throughout the normalized tables via [DatabaseTermIdsCleaner]

[DatabaseTermIdsCleaner] is needed to ensure that no data that was intended to be deleted is still publicly accessible (as the wbt_ tables are a public index).

[wbt_item_terms]: @ref md_docs_sql_wbt_item_terms
[wbt_property_terms]: @ref md_docs_sql_wbt_property_terms
[wbt_term_in_lang]: @ref md_docs_sql_wbt_term_in_lang
[wbt_text_in_lang]: @ref md_docs_sql_wbt_text_in_lang
[wbt_text]: @ref md_docs_sql_wbt_text
[wbt_type]: @ref md_docs_sql_wbt_type
[EntityHandler]: @ref Wikibase::Repo::Content::EntityHandler
[EntityTermStoreWriter]: @ref Wikibase::Lib::Store::EntityTermStoreWriter
[DatabaseItemTermStore]: @ref Wikibase::Lib::Store::Sql::Terms::DatabaseItemTermStore
[DatabasePropertyTermStore]: @ref Wikibase::Lib::Store::Sql::Terms::DatabasePropertyTermStore
[DatabaseTermIdsCleaner]: @ref Wikibase::Lib::Store::Sql::Terms::DatabaseTermIdsCleaner
[DatabaseTermIdsAcquirer]: @ref Wikibase::Lib::Store::Sql::Terms::DatabaseTermIdsAcquirer
[Wikibase\Lib\Store\Sql\Terms]: @ref Wikibase::Lib::Store::Sql::Terms
[vendor component]: @ref libraries
[AddNormalizedTermsTablesDDL.sql]: @ref AddNormalizedTermsTablesDDL.sql
