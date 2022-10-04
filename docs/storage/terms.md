# Item & Property Terms

Secondary storage for Item and Property terms in SQL is needed for efficient and atomic lookup and query of the terms of multiple entities in multiple languages.

For example, when rendering an Item page the labels of all other entities being referred to need to be known.
The alternative to secondary storage would be loading each of the full entities in order to lookup the terms needed.

The code for the storage lives in the [Wikibase\Lib\Store\Sql\Terms] namespace.

Writing to the secondary storage happens through a deferred update after each edit on entities. This is to make saving edits faster and more atomic which also means reducing the failure rate of saving edits. As the result, secondary storage might not be always completely in sync with the actual terms stored in the primary storage.

Briefly in code:
 - ItemTermStoreWriter and PropertyTermStoreWriter are the interfaces at the bottom of the term storage tree.
   - These interfaces are provided by the `data-model-services` [vendor component]
 - [EntityTermStoreWriter] joins these stores in an interface that can generically save either Item or Property terms.

### Secondary Storage

The storage is made up of multiple normalized tables, all prefixed with "wbt_".DatabaseTermInLangIdsAcquirer
The tables were created by [term_store.sql] which includes some documentation.

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

**Lookup terms of an entity**

Lookup of the terms of an entity can be achieved by starting with the [wbt_item_terms] or [wbt_property_terms] tables where you will find integer representations of Item and Property identifiers.

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

For properties you can do something like:

```sql
SELECT
  wbpt_property_id as id,
  wby_name as type,
  wbxl_language as language,
  wbx_text as text
FROM wbt_property_terms
LEFT JOIN wbt_term_in_lang ON wbpt_term_in_lang_id = wbtl_id
LEFT JOIN wbt_type ON wbtl_type_id = wby_id
LEFT JOIN wbt_text_in_lang ON wbtl_text_in_lang_id = wbxl_id
LEFT JOIN wbt_text ON wbxl_text_id = wbx_id
WHERE wbpt_property_id = 10;
```

**Lookup all entities that use a certain term**

Lookup of entities from a term string can be achieved by starting with the [wbt_text] table which contains the text for all terms or all types for both Items and Properties.

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
WHERE wby_name = 'label'
AND wbxl_language = 'en'
AND wbx_text = 'Berlin';
```

For properties you can do something like:

```sql
SELECT
  wbpt_property_id as id,
  wby_name as type,
  wbxl_language as language,
  wbx_text as text
FROM wbt_property_terms
LEFT JOIN wbt_term_in_lang ON wbpt_term_in_lang_id = wbtl_id
LEFT JOIN wbt_type ON wbtl_type_id = wby_id
LEFT JOIN wbt_text_in_lang ON wbtl_text_in_lang_id = wbxl_id
LEFT JOIN wbt_text ON wbxl_text_id = wbx_id
WHERE wby_name = 'label'
AND wbxl_language = 'en'
AND wbx_text = 'instance of';
```

#### Updating

**Process outline**

 - Term secondary storage is currently written to after edits are saved in MediaWiki's "Secondary data updates". See [ItemHandler::getSecondaryDataUpdates()] and [PropertyHandler::getSecondaryDataUpdates()] implementations.
 - When term changes happen a series of ID acquisitions occur in [DatabaseTermInLangIdsAcquirer]. (Finding IDs that already exist in the storage that will be needed for future inserts)
 - When new terms are being introduced rows that do not appear in the acquisition will be inserted.
 - Actual insertion and deletion of the terms in the `wbt_item_terms` and `wbt_property_terms` tables is done in [DatabaseItemTermStoreWriter] and [DatabasePropertyTermStoreWriter].
 - When term changes result in some terms potentially being no longer being used across the whole store a [CleanTermsIfUnusedJob] job will be scheduled to remove the rows.
 - The job removes data from other tables using a [DatabaseInnerTermStoreCleaner].

**Keeping the store clean**

The tables in the store are cleaned up so that data that is totally removed from entities is also totally removed from the store.
This is important for cases such as Wikidata that has publicly accessible database replicas of this information.

[wbt_item_terms]: @ref docs_sql_wbt_item_terms
[wbt_property_terms]: @ref docs_sql_wbt_property_terms
[wbt_term_in_lang]: @ref docs_sql_wbt_term_in_lang
[wbt_text_in_lang]: @ref docs_sql_wbt_text_in_lang
[wbt_text]: @ref docs_sql_wbt_text
[wbt_type]: @ref docs_sql_wbt_type
[ItemHandler::getSecondaryDataUpdates()]: @ref Wikibase::Repo::Content::ItemHandler::getSecondaryDataUpdates()
[PropertyHandler::getSecondaryDataUpdates()]: @ref Wikibase::Repo::Content::PropertyHandler::getSecondaryDataUpdates()
[EntityTermStoreWriter]: @ref Wikibase::Lib::Store::EntityTermStoreWriter
[DatabaseItemTermStoreWriter]: @ref Wikibase::Lib::Store::Sql::Terms::DatabaseItemTermStoreWriter
[DatabasePropertyTermStoreWriter]: @ref Wikibase::Lib::Store::Sql::Terms::DatabasePropertyTermStoreWriter
[DatabaseInnerTermStoreCleaner]: @ref Wikibase::Lib::Store::Sql::Terms::DatabaseInnerTermStoreCleaner
[CleanTermsIfUnusedJob]: @ref Wikibase::Lib::Store::Sql::Terms::CleanTermsIfUnusedJob
[DatabaseTermInLangIdsAcquirer]: @ref Wikibase::Lib::Store::Sql::Terms::DatabaseTermInLangIdsAcquirer
[Wikibase\Lib\Store\Sql\Terms]: @ref Wikibase::Lib::Store::Sql::Terms
[vendor component]: @ref libraries
[term_store.sql]: @ref term_store.sql
