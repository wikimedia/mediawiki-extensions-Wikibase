# wbt_item_terms

Stores a record per term per item per language.
This table is expected to be the tallest in this group of tables.
This table is very similar to / identical to [wbt_property_terms], but for items.

Part of the @ref docs_storage_terms storage system.

**Fields:**

-   wbit_id - an auto increment field
-   wbit_item_id - numeric value of the item ID. So Q64 # 64
-   wbit_term_in_lang_id - reference to the [wbt_term_in_lang] table

```
+----------------------+---------------------+------+-----+---------+----------------+
| Field                | Type                | Null | Key | Default | Extra          |
+----------------------+---------------------+------+-----+---------+----------------+
| wbit_id              | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| wbit_item_id         | int(10) unsigned    | NO   | MUL | NULL    |                |
| wbit_term_in_lang_id | int(10) unsigned    | NO   | MUL | NULL    |                |
+----------------------+---------------------+------+-----+---------+----------------+
```

Using an integer to represent the item identifier was a design decision allowing for smaller tables and faster querying.

**Extra Indexes:**
 - UNIQUE wbit_term_in_lang, wbit_item_id
 - wbit_item_id

**Example data:**

| wbit_id  | wbit_item_id  | wbit_term_in_lang_id  |
| -------- | ------------- | --------------------- |
| 1        | 99            | 5678                  |
| 2        | 99            | 1111                  |
| 3        | 12            | 4533                  |

[wbt_property_terms]: @ref docs_sql_wbt_property_terms
[wbt_term_in_lang]: @ref docs_sql_wbt_term_in_lang
