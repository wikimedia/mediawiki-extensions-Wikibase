Stores a record per text value that are used in different terms in different languages.

Part of the \ref md_docs_storage_terms storage system.

```
+----------+------------------+------+-----+---------+----------------+
| Field    | Type             | Null | Key | Default | Extra          |
+----------+------------------+------+-----+---------+----------------+
| wbx_id   | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbx_text | varbinary(255)   | NO   | UNI | NULL    |                |
+----------+------------------+------+-----+---------+----------------+
```

**Example data:**

| wbx_id   | wbx_text             |
| -------- | -------------------- |
| 65680880 | Some Term Text       |
| 66256338 | Some Other Term Text |
