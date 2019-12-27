Part of the \ref topic_change-propagation system on a Repo.
See also \ref topic_usagetracking.

- cs_row_id - Auto-increment row id for internal use; primary key.
- cs_entity_id - The ID of the entity used on the client wiki.
- cs_subscriber_id - The global ID (as used by the sites table) of the wiki using the entity (by WMF conventions, the same as the database name).

```
+------------------+----------------+------+-----+---------+----------------+
| Field            | Type           | Null | Key | Default | Extra          |
+------------------+----------------+------+-----+---------+----------------+
| cs_row_id        | bigint(20)     | NO   | PRI | NULL    | auto_increment |
| cs_entity_id     | varbinary(255) | NO   | MUL | NULL    |                |
| cs_subscriber_id | varbinary(255) | NO   | MUL | NULL    |                |
+------------------+----------------+------+-----+---------+----------------+
```
