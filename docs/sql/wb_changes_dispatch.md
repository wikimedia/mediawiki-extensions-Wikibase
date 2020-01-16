This table contains one row per client wiki, with the following information:

Part of the \ref md_docs_topics_change-propagation system on a Repo.

**Fields:**

* chd_site - the target wiki with its global site ID.
* chd_db - the logical database name of the client wiki.
* chd_seen - the last change ID that was sent to this client wiki.
* chd_touched - the time at which this row was last updated. This is useful only for reporting and debugging.
* chd_lock - the name of some kind of lock that some process currently holds on this row.
  * The lock name should indicate the locking mechanism.
  * The locking mechanism should be able to reliably detect stale locks belonging to dead processes.
* chd_disabled - set to 1 to disable dispatching for this wiki.

```
+--------------+---------------------+------+-----+----------------+-------+
| Field        | Type                | Null | Key | Default        | Extra |
+--------------+---------------------+------+-----+----------------+-------+
| chd_site     | varbinary(32)       | NO   | PRI | NULL           |       |
| chd_db       | varbinary(32)       | NO   |     | NULL           |       |
| chd_seen     | int(11)             | NO   | MUL | 0              |       |
| chd_touched  | varbinary(14)       | NO   | MUL | 00000000000000 |       |
| chd_lock     | varbinary(64)       | YES  |     | NULL           |       |
| chd_disabled | tinyint(3) unsigned | NO   |     | 0              |       |
+--------------+---------------------+------+-----+----------------+-------+
```

**Extra Indexes:**
 - chd_seen
 - chd_touched
