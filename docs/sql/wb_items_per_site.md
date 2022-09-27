# wb_items_per_site

This table holds links from items to Client articles.

Part of the @ref docs_storage_sitelinks storage system.

**Fields:**

 - **ips_row_id**: Unique ID
 - **ips_item_id**: Numeric representation of the Qid. Q64 -> 64.
   - Can be joined against page.page_title if the namespace for Items is known.
 - **ips_site_id**: Site global ID, e.g. enwiktionary
 - **ips_site_page**: Page name on site

```
+---------------+---------------------+------+-----+---------+----------------+
| Field         | Type                | Null | Key | Default | Extra          |
+---------------+---------------------+------+-----+---------+----------------+
| ips_row_id    | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| ips_item_id   | int(10) unsigned    | NO   | MUL | NULL    |                |
| ips_site_id   | varbinary(32)       | NO   | MUL | NULL    |                |
| ips_site_page | varbinary(310)      | NO   |     | NULL    |                |
+---------------+---------------------+------+-----+---------+----------------+
```

**Extra indexes:**
 - ips_item_id
 - ips_site_id & ips_site_page (UNIQUE)

**Example data:**

| ips_row_id  | ips_item_id | ips_site_id | ips_site_page |
| ------------| ----------- | ----------- | ------------- |
| 12          | 100         | enwiki      | Berlin        |
| 400         | 100         | dewiki      | Berlin        |
| 9000        | 624999111   | otherwiki   | SomePage      |
