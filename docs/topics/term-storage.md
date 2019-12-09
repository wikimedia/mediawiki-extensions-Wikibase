# Item and Property Term Secondary Storage {#topic_term-storage}

In code: - `ItemTermStore` and `PropertyTermStore` are the interfaces at the bottom of the term storage tree. - These interfaces are currently in the `term-store` vendor component. - Implementations exist to write to the new and old storage as well as other implementations allowing mixed reading and writing. - `EntityTermStoreWriter` joins these stores in an interface that can generically save either Item or Property terms. - `EntityHandler` takes a `EntityTermStoreWriter` which is used in a few data updates relating to saving and deleting entities.

## Legacy Secondary Storage

In the past (pre 2020) terms were stored in a single large database table called wb_terms. During 2019 a migration process was carried out (and is still being carried out) migrating the terms to a new schema. The "Epic" task for this was https://phabricator.wikimedia.org/T208425

## New Secondary Storage

### Database tables

The new storage is made up of multiple database tables, all prefixed with "wbt_". be the longest one These tables represent a normalized form of the wb_terms table.

The tables were created by AddNormalizedTermsTablesDDL.sql which includes some documentation.

Here is a great plain text version of the relations that are going on:

<pre>
wbt_item_terms --------\
                        ---- wbt_term_in_lang --- wbt_text_in_lang
wbt_property_terms ----/          \                    \
                                   \-- wbt_type         \-- wbt_text
</pre>

Checking data for these tables involves lots of joins.
The below query selects all terms in the tables for item Q1 and can be used as a starting point for data exploration:
<pre>
SELECT
  wbit_item_id as id,
  wby_name as type,
  wbxl_language as language,
  wbx_text as text
FROM wbt_item_terms
LEFT JOIN wbt_term_in_lang ON wbit_term_in_lang_id = wbtl_id
LEFT JOIN wbt_type ON wbtl_type_id = wby_id
LEFT JOIN wbt_text_in_lang ON wbtl_text_in_lang_id = wbxl_id
LEFT JOIN wbt_text ON wbxl_text_id = wbx_id
WHERE wbit_item_id = 1;
</pre>

And now for a summary of the tables including human readable schema description and some examples.

#### wbt_item_terms

Stores a record per term per item per language. This table is expected to be the tallest in this group of tables.

<pre>
+----------------------+---------------------+------+-----+---------+----------------+
| Field                | Type                | Null | Key | Default | Extra          |
+----------------------+---------------------+------+-----+---------+----------------+
| wbit_id              | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| wbit_item_id         | int(10) unsigned    | NO   | MUL | NULL    |                |
| wbit_term_in_lang_id | int(10) unsigned    | NO   | MUL | NULL    |                |
+----------------------+---------------------+------+-----+---------+----------------+
</pre>

-   wbit_id - an auto increment field
-   wbit_item_id - numeric value of the item ID. So Q64 # 64
-   wbit_term_in_lang_id - reference to the 'wbt_term_in_lang' table

#### wbt_property_terms

Stores a record per term per property per language. This table is very similar to / identical to wbt_item_terms, but for properties.

<pre>
+----------------------+------------------+------+-----+---------+----------------+
| Field                | Type             | Null | Key | Default | Extra          |
+----------------------+------------------+------+-----+---------+----------------+
| wbpt_id              | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbpt_property_id     | int(10) unsigned | NO   | MUL | NULL    |                |
| wbpt_term_in_lang_id | int(10) unsigned | NO   | MUL | NULL    |                |
+----------------------+------------------+------+-----+---------+----------------+
</pre>

-   wbpt_id - an auto increment field
-   wbpt_property_id - numeric value of the item ID. So P64 # 64
-   wbpt_term_in_lang_id - reference to the 'wbt_term_in_lang' table

#### wbt_term_in_lang

Stores a record per term per text per language.

<pre>
+----------------------+------------------+------+-----+---------+----------------+
| Field                | Type             | Null | Key | Default | Extra          |
+----------------------+------------------+------+-----+---------+----------------+
| wbtl_id              | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbtl_type_id         | int(10) unsigned | NO   | MUL | NULL    |                |
| wbtl_text_in_lang_id | int(10) unsigned | NO   | MUL | NULL    |                |
+----------------------+------------------+------+-----+---------+----------------+
</pre>

-   wbtl_id - an auto increment field
-   wbtl_type_id - reference to the 'wbt_type' table
-   wbtl_text_in_lang_id - reference to the 'wbt_text_in_lang' table

#### wbt_text_in_lang

Stores a record per term text per language.

<pre>
+---------------+------------------+------+-----+---------+----------------+
| Field         | Type             | Null | Key | Default | Extra          |
+---------------+------------------+------+-----+---------+----------------+
| wbxl_id       | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbxl_language | varbinary(10)    | NO   | MUL | NULL    |                |
| wbxl_text_id  | int(10) unsigned | NO   | MUL | NULL    |                |
+---------------+------------------+------+-----+---------+----------------+
</pre>

-   wbxl_id - an auto increment field
-   wbxl_language - Language code, e.g. 'en', or 'fr', or 'de', or 'zh-hans'
-   wbxl_text_id - reference to the 'wbt_text' table

#### wbt_text

Stores a record per text value that are used in different terms in different languages.

<pre>
+----------+------------------+------+-----+---------+----------------+
| Field    | Type             | Null | Key | Default | Extra          |
+----------+------------------+------+-----+---------+----------------+
| wbx_id   | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wbx_text | varbinary(255)   | NO   | UNI | NULL    |                |
+----------+------------------+------+-----+---------+----------------+
</pre>

For example:

<pre>
+----------+--------------------------------------------------------------+
| wbx_id   | wbx_text                                                     |
+----------+--------------------------------------------------------------+
| 65680880 | Some Term Text                                               |
| 66256338 | Some Other Term Text                                         |
+----------+--------------------------------------------------------------+
</pre>

#### wbt_type

Normalized term type names. The simplest of the above tables.

<pre>
+----------+------------------+------+-----+---------+----------------+
| Field    | Type             | Null | Key | Default | Extra          |
+----------+------------------+------+-----+---------+----------------+
| wby_id   | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| wby_name | varbinary(45)    | NO   | UNI | NULL    |                |
+----------+------------------+------+-----+---------+----------------+
</pre>

For example:

<pre>
+--------+-------------+
| wby_id | wby_name    |
+--------+-------------+
|      1 | label       |
|      2 | description |
|      3 | alias       |
+--------+-------------+
</pre>
