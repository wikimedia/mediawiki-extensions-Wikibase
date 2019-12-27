Part of the \ref topic_usagetracking system on a Client.

 - eu_row_id - auto-increment row ID for internal use. Primary key.
 - eu_entity_id - Serialized entity ID for the entity that has usage
 - eu_aspect - Which aspect of the entity is being used. (See @ref topic_usagetracking)
 - eu_page_id - the ID of the page using the entity; refers to page.page_id.

```
+--------------+----------------+------+-----+---------+----------------+
| Field        | Type           | Null | Key | Default | Extra          |
+--------------+----------------+------+-----+---------+----------------+
| eu_row_id    | bigint(20)     | NO   | PRI | NULL    | auto_increment |
| eu_entity_id | varbinary(255) | NO   | MUL | NULL    |                |
| eu_aspect    | varbinary(37)  | NO   |     | NULL    |                |
| eu_page_id   | int(11)        | NO   | MUL | NULL    |                |
+--------------+----------------+------+-----+---------+----------------+
```

**Extra Indexes:**

```
+------------------+------------+--------------+--------------+--------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
| Table            | Non_unique | Key_name     | Seq_in_index | Column_name  | Collation | Cardinality | Sub_part | Packed | Null | Index_type | Comment | Index_comment |
+------------------+------------+--------------+--------------+--------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
| wbc_entity_usage |          0 | PRIMARY      |            1 | eu_row_id    | A         |     7272518 |     NULL | NULL   |      | BTREE      |         |               |
| wbc_entity_usage |          0 | eu_entity_id |            1 | eu_entity_id | A         |     3636259 |     NULL | NULL   |      | BTREE      |         |               |
| wbc_entity_usage |          0 | eu_entity_id |            2 | eu_aspect    | A         |     7272518 |     NULL | NULL   |      | BTREE      |         |               |
| wbc_entity_usage |          0 | eu_entity_id |            3 | eu_page_id   | A         |     7272518 |     NULL | NULL   |      | BTREE      |         |               |
| wbc_entity_usage |          1 | eu_page_id   |            1 | eu_page_id   | A         |      404028 |     NULL | NULL   |      | BTREE      |         |               |
| wbc_entity_usage |          1 | eu_page_id   |            2 | eu_entity_id | A         |     7272518 |     NULL | NULL   |      | BTREE      |         |               |
+------------------+------------+--------------+--------------+--------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
```

 - UNIQUE eu_entity_id , eu_aspect, eu_page_id - record one usage per page per aspect of an entity
 - eu_page_id, eu_entity_id - look up (and especially, delete) usage entries by page id

**Example data:**

| eu_row_id | eu_entity_id | eu_aspect | eu_page_id |
|-----------|--------------|-----------|------------|
| 158829644 | Q99          | O         |   73443972 |
|  88145275 | L10102       | L         |   56193021 |
| 157778764 | L10151-S1    | L         |   53636569 |
|  91511974 | Q645         | D.en      |   3334     |
|  96856295 | P455         | L.en      |   3334     |
