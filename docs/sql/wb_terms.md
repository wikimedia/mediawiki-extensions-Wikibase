This table is deprecated and will be removed in 2020.

Part of the LEGACY \ref md_docs_storage_terms storage system.

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
