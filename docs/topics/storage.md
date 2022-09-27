# Storage

## Topic Areas

* @subpage docs_storage_entities
* @subpage docs_storage_id-counters
* @subpage docs_storage_propertyinfo
* @subpage docs_storage_sitelinks
* @subpage docs_storage_terms
* @subpage docs_storage_terms-caching

## SQL Tables {#sql_tables}

**Repo**

* @ref docs_topics_change-propagation
  * @subpage docs_sql_wb_changes
  * @subpage docs_sql_wb_changes_subscription
* @ref docs_storage_id-counters
  * @subpage docs_sql_wb_id_counters
* @ref docs_storage_sitelinks
  * @subpage docs_sql_wb_items_per_site
* @ref docs_storage_propertyinfo
  * @subpage docs_sql_wb_property_info
* @ref docs_storage_terms
  * @subpage docs_sql_wbt_item_terms
  * @subpage docs_sql_wbt_property_terms
  * @subpage docs_sql_wbt_term_in_lang
  * @subpage docs_sql_wbt_text_in_lang
  * @subpage docs_sql_wbt_type
  * @subpage docs_sql_wbt_text

**Client**

* @ref docs_topics_change-propagation
  * @subpage docs_sql_wbc_entity_usage (See also @ref docs_topics_usagetracking)

## Existing table changes

The following changes are done to the default MediaWiki tables:
* [content_models]
  * Repo: Add new content models
    * wikibase-item
    * wikibase-property
* [page_props]
  * Repo: Add some counters:
    * wb-claims
    * wb-identifiers
    * wb-sitelinks
  * Client:
    * wb_item
* ...

[content_models]: https://www.mediawiki.org/wiki/Manual:Content_models_table
[page_props]: https://www.mediawiki.org/wiki/Manual:Page_props_table
