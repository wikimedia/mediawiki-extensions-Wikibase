# wbt_term_in_lang

Stores a record per term with type in a language.

Part of the @ref docs_storage_terms storage system.

**Fields:**

-   wbtl_id - an auto increment field
-   wbtl_type_id - term type ID (`1` for a label, `2` for a description, `3` for an alias)
-   wbtl_text_in_lang_id - reference to the [wbt_text_in_lang] table

```
+----------------------+------------------+------+-----+---------+----------------+
| Field                | Type             | Null | Key | Default | Extra          |
+----------------------+------------------+------+-----+---------+----------------+
| wbtl_id              | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbtl_type_id         | int(10) unsigned | NO   | MUL | NULL    |                |
| wbtl_text_in_lang_id | int(10) unsigned | NO   | MUL | NULL    |                |
+----------------------+------------------+------+-----+---------+----------------+
```

**Extra Indexes:**
 - UNIQUE wbtl_text_in_lang_id, wbtl_type_id
 - wbtl_type_id

[wbt_text_in_lang]: @ref docs_sql_wbt_text_in_lang
