# wbt_type

Normalized term type names.

Part of the @ref docs_storage_terms storage system.

This table:
 - Allows storing the knowledge of an integer to term type mapping in the DB (rather than the application).
 - Will likely always be cached in memory by the SQL server.

**Fields:**

```
+----------+------------------+------+-----+---------+----------------+
| Field    | Type             | Null | Key | Default | Extra          |
+----------+------------------+------+-----+---------+----------------+
| wby_id   | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wby_name | varbinary(45)    | NO   | UNI | NULL    |                |
+----------+------------------+------+-----+---------+----------------+
```


**Extra Indexes:**
 - UNIQUE wby_name

**Example data:**

| wby_id | wby_name    |
| -------| ----------- |
| 1      | label       |
| 2      | description |
| 3      | alias       |
