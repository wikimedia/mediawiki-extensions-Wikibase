Normalized term type names.

Part of the \ref md_docs_storage_terms storage system.

This table:
 - Allows storing the knowledge of an integer to term type mapping in the DB (rather than the application).
 - Will likely always be cached in memory by the SQL server.

```
+----------+------------------+------+-----+---------+----------------+
| Field    | Type             | Null | Key | Default | Extra          |
+----------+------------------+------+-----+---------+----------------+
| wby_id   | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wby_name | varbinary(45)    | NO   | UNI | NULL    |                |
+----------+------------------+------+-----+---------+----------------+
```

**Example data:**

| wby_id | wby_name    |
| -------| ----------- |
| 1      | label       |
| 2      | description |
| 3      | alias       |
