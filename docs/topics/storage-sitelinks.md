# Sitelink Secondary Storage {#topic_sitelink-storage}

Secondary storage is needed for sitelinks in order to have:
 - Uniqueness of sitelinks across all Wikibase Items.
 - Lookup of an Item using a site identifier and page name.

### Code

TBA

### SQL table (wb_items_per_site)

This table holds links from items to Client articles.

**Fields:**

 - **ips_row_id**: Unique ID
 - **ips_item_id**: Numeric representation of the Qid. Q64 -> 64.
   - Can be joined against page.page_title if the namespace for Items is known.
 - **ips_site_id**: Site identifier
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
