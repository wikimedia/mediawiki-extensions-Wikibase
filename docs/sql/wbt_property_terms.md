# wbt_property_terms

Stores a record per term per property per language.
This table is very similar to / identical to [wbt_item_terms], but for properties.

Part of the @ref docs_storage_terms storage system.

**Fields:**

-   wbpt_id - an auto increment field
-   wbpt_property_id - numeric value of the item ID. So P64 # 64
-   wbpt_term_in_lang_id - reference to the [wbt_term_in_lang] table

```
+----------------------+------------------+------+-----+---------+----------------+
| Field                | Type             | Null | Key | Default | Extra          |
+----------------------+------------------+------+-----+---------+----------------+
| wbpt_id              | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbpt_property_id     | int(10) unsigned | NO   | MUL | NULL    |                |
| wbpt_term_in_lang_id | int(10) unsigned | NO   | MUL | NULL    |                |
+----------------------+------------------+------+-----+---------+----------------+
```

Using an integer to represent the item identifier was a design decision allowing for smaller tables and faster querying.

**Extra Indexes:**
 - UNIQUE wbit_term_in_lang, wbit_property_id
 - wbit_property_id

**Example data:**

| wbpt_id  | wbpt_property_id  | wbpt_term_in_lang_id  |
| -------- | ----------------- | --------------------- |
| 1        | 9                 | 5678                  |
| 6        | 9                 | 1111                  |
| 7        | 10                | 888                   |

[wbt_item_terms]: @ref docs_sql_wbt_item_terms
[wbt_term_in_lang]: @ref docs_sql_wbt_term_in_lang
