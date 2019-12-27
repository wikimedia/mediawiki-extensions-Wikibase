Part of the \ref topic_change-propagation system on a Repo.
See also \ref topic_usagetracking.

- cs_row_id - Auto-increment row id for internal use; primary key.
- cs_entity_id - The ID of the entity being subscribed to by the client page.
- cs_subscriber_id - The global ID (as used by the sites table) of the subscriber (by WMF conventions, the same as the database name).

```
+------------------+----------------+------+-----+---------+----------------+
| Field            | Type           | Null | Key | Default | Extra          |
+------------------+----------------+------+-----+---------+----------------+
| cs_row_id        | bigint(20)     | NO   | PRI | NULL    | auto_increment |
| cs_entity_id     | varbinary(255) | NO   | MUL | NULL    |                |
| cs_subscriber_id | varbinary(255) | NO   | MUL | NULL    |                |
+------------------+----------------+------+-----+---------+----------------+
```

*NOTE*: When tracking usage of entities from multiple repos, we either need distinct ID prefixes, or one table per repo, or both. An additional eu\_entity\_repo column would introduce a huge amount of redundant data, and would not play well with indexes.

**Extra Indexes:**

```
+-------------------------+------------+------------------+--------------+------------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
| Table                   | Non_unique | Key_name         | Seq_in_index | Column_name      | Collation | Cardinality | Sub_part | Packed | Null | Index_type | Comment | Index_comment |
+-------------------------+------------+------------------+--------------+------------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
| wb_changes_subscription |          0 | PRIMARY          |            1 | cs_row_id        | A         |    65612922 |     NULL | NULL   |      | BTREE      |         |               |
| wb_changes_subscription |          0 | cs_entity_id     |            1 | cs_entity_id     | A         |    65612922 |     NULL | NULL   |      | BTREE      |         |               |
| wb_changes_subscription |          0 | cs_entity_id     |            2 | cs_subscriber_id | A         |    65612922 |     NULL | NULL   |      | BTREE      |         |               |
| wb_changes_subscription |          1 | cs_subscriber_id |            1 | cs_subscriber_id | A         |      141712 |     NULL | NULL   |      | BTREE      |         |               |
+-------------------------+------------+------------------+--------------+------------------+-----------+-------------+----------+--------+------+------------+---------+---------------+
```

 - cs_entity_id, cs_subscriber_id - look up a subscription, or all subscribers of an entity
 - cs_subscriber_id - look up all subscriptions of a subscriber
