# Id Counters {#topic_id-counter-storage}

Entity IDs are created based on counters maintained by Wikibase in the wb_id_counters table.
 - Some extensions also use this table, such as Lexeme.
 - Other extensions such as EntitySchema choose not to use the table (instead creating their own).

### Code

The \ref IdGenerator interface is generally used when interacting with this storage.
 - \ref SqlIdGenerator is the oldest ID generator implementation that works for all DB types.
 - \ref UpsertSqlIdGenerator is a newer 'better' generator that only works for MySql.

### SQL table (wb_id_counters)

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
| id_type  | varbinary(32)    | NO   | PRI | NULL    |       |
+----------+------------------+------+-----+---------+-------+
```

**Example data:**

| id_value  | id_type           |
| ----------| ----------------- |
| 79601251  | wikibase-item     |
| 229724    | wikibase-lexeme   |
| 7728      | wikibase-property |
