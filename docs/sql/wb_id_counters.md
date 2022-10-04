# wb_id_counters

Part of the @ref docs_storage_id-counters storage system.

**Fields:**

 - **id_value**
   - The value of the counter; this is used to get a new unique id for the next new item or property or lexeme entry created.
 - **id_type**
   - Unique name of the type of identifier
   - Standalone Wikibase will only have the following rows:
     - wikibase-item
     - wikibase-property

```
+----------+------------------+------+-----+---------+-------+
| Field    | Type             | Null | Key | Default | Extra |
+----------+------------------+------+-----+---------+-------+
| id_value | int(10) unsigned | NO   |     | NULL    |       |
| id_type  | varbinary(32)    | NO   |     | NULL    |       |
+----------+------------------+------+-----+---------+-------+
```

**Extra Indexes:**
 - id_type

**Example data:**

| id_value  | id_type           |
| ----------| ----------------- |
| 79601251  | wikibase-item     |
| 229724    | wikibase-lexeme   |
| 7728      | wikibase-property |
