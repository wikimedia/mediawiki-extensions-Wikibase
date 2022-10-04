# wb_items_per_site

Part of the @ref docs_storage_propertyinfo storage system.

**Fields:**

 - **pi_property_id** - Numeric representation of the Pid. P64 -> 64.
   - Can be joined against page.page_title if the namespace for Properties is known.
 - **pi_type** - The data type of the property.
   - This is repeated from the content of pi_info to allow properties to be queried by type efficiently.
 - **pi_info** - A JSON BLOB containing information associated with the property.

```
+----------------+------------------+------+-----+---------+-------+
| Field          | Type             | Null | Key | Default | Extra |
+----------------+------------------+------+-----+---------+-------+
| pi_property_id | int(10) unsigned | NO   | PRI | NULL    |       |
| pi_type        | varbinary(32)    | NO   | MUL | NULL    |       |
| pi_info        | blob             | NO   |     | NULL    |       |
+----------------+------------------+------+-----+---------+-------+
```

**Extra Indexes:**
 - pi_type - Allowing queries by Property type (Eg. string, url etc.)

**Example data:**

| pi_property_id  | pi_type      | pi_info                 |
| ----------------| ------------ | ----------------------- |
| 18              | commonsMedia | {"type":"commonsMedia","formatterURL":"https:\/\/commons.wikimedia.org\/wiki\/File:$1"} |
| 20              | wikibase-item| {"type":"wikibase-item"} |
| 353             | external-id  | {"type":"external-id","formatterURL":"https:\/\/www.genenames.org\/tools\/search\/#!\/all?query=$1"} |
