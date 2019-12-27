The change buffer holds information about each change, stored in the wb_changes table, to be accessed by the client wikis when processing the respective change.
This is similar to MediaWiki's recentchanges table.
The table structure is as follows:

Part of the \ref topic_change-propagation system on a Repo.

* change_id
  * An int(10) with an autoincrement id identifying the change.
* change_type
  * A varchar(25) representing the kind of change. It has the form ''wikibase-&lt;entity-type&gt;~&lt;action&gt;'', e.g. “wikibase-item~add”.
  * Well known entity types are “item” and “property”. Custom entity types will define their own type names.
  * Known actions: “update”, “add”, “remove”, “restore”
* change_time
  * A varbinary(14) the time at which the edit was made
* change_object_id
  * A varbinary(14) containing the entity ID
* change_revision_id
  * A int(10) containing the revision ID
* change_user_id
  * A int(10) containing the original (repository) user id, or 0 for logged out users.
* change_info
  * A mediumblob containing a JSON structure with additional information about the change. Well known top level fields are:
    * “diff”
      * A serialized diff, as produced by EntityDiffer
    * “metadata”
      * A JSON object representing essential revision meta data, using the following fields:
        * “central_user_id”
          * The central user ID (int). 0 if the repo is not connected to a central user system, the action was by a logged out user, the particular user is not attached on the repo, or the user is restricted (uses AUDIENCE_PUBLIC)
        * “user_text”
          * The user name (string)
        * “page_id”
          * The id of the wiki page containing the entity on the repo (int)
        * “rev_id”
          * The id of the revision created by this change on the repo (int)
        * “parent_id”
          * The id of the parent revision of this change on the repo (int)
        * “comment”
          * The edit summary for the change
        * “bot”
          * Whether the change was performed as a bot edit (0 or 1)

```
+--------------------+------------------+------+-----+---------+----------------+
| Field              | Type             | Null | Key | Default | Extra          |
+--------------------+------------------+------+-----+---------+----------------+
| change_id          | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| change_type        | varbinary(25)    | NO   | MUL | NULL    |                |
| change_time        | varbinary(14)    | NO   | MUL | NULL    |                |
| change_object_id   | varbinary(14)    | NO   | MUL | NULL    |                |
| change_revision_id | int(10) unsigned | NO   | MUL | NULL    |                |
| change_user_id     | int(10) unsigned | NO   | MUL | NULL    |                |
| change_info        | mediumblob       | NO   |     | NULL    |                |
+--------------------+------------------+------+-----+---------+----------------+
```
