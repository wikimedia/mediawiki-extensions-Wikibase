# wbt_text_in_lang

Stores a record per term text per language.

Part of the @ref docs_storage_terms storage system.

**Fields:**

-   wbxl_id - an auto increment field
-   wbxl_language - Language code, e.g. 'en', or 'fr', or 'de', or 'zh-hans'
-   wbxl_text_id - reference to the [wbt_text] table

```
+---------------+------------------+------+-----+---------+----------------+
| Field         | Type             | Null | Key | Default | Extra          |
+---------------+------------------+------+-----+---------+----------------+
| wbxl_id       | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbxl_language | varbinary(10)    | NO   | MUL | NULL    |                |
| wbxl_text_id  | int(10) unsigned | NO   | MUL | NULL    |                |
+---------------+------------------+------+-----+---------+----------------+
```

**Extra Indexes:**
 - UNIQUE wbxl_text_id, wbxl_language
 - wbxl_language

[wbt_text]: @ref docs_sql_wbt_text
