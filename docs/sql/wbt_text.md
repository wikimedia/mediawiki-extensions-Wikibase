# wbt_text

Stores a record per text value that are used in different terms in different languages.

Part of the @ref docs_storage_terms storage system.

**Fields:**

```
+----------+------------------+------+-----+---------+----------------+
| Field    | Type             | Null | Key | Default | Extra          |
+----------+------------------+------+-----+---------+----------------+
| wbx_id   | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbx_text | varbinary(255)   | NO   | UNI | NULL    |                |
+----------+------------------+------+-----+---------+----------------+
```

**Extra Indexes:**
 - UNIQUE wbx_text

**Example data:**

| wbx_id   | wbx_text             |
| -------- | -------------------- |
| 65680880 | Some Term Text       |
| 66256338 | Some Other Term Text |
