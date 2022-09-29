# Storage

## Topic Areas

* @subpage md_docs_storage_entities
* @subpage md_docs_storage_id-counters
* @subpage md_docs_storage_propertyinfo
* @subpage md_docs_storage_sitelinks
* @subpage md_docs_storage_terms
* @subpage md_docs_storage_terms-caching

## SQL Tables {#sql_tables}

**Repo**

* @ref md_docs_topics_change-propagation
  * @subpage md_docs_sql_wb_changes
  * @subpage md_docs_sql_wb_changes_subscription
* @ref md_docs_storage_id-counters
  * @subpage md_docs_sql_wb_id_counters
* @ref md_docs_storage_sitelinks
  * @subpage md_docs_sql_wb_items_per_site
* @ref md_docs_storage_propertyinfo
  * @subpage md_docs_sql_wb_property_info
* @ref md_docs_storage_terms
  * @subpage md_docs_sql_wbt_item_terms
  * @subpage md_docs_sql_wbt_property_terms
  * @subpage md_docs_sql_wbt_term_in_lang
  * @subpage md_docs_sql_wbt_text_in_lang
  * @subpage md_docs_sql_wbt_type
  * @subpage md_docs_sql_wbt_text

**Client**

* @ref md_docs_topics_change-propagation
  * @subpage md_docs_sql_wbc_entity_usage (See also @ref md_docs_topics_usagetracking)

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
