# Summaries

Wikibase uses “magic” edit summaries so that users can understand the contents of edits in their own language.
They are created by SummaryFormatter, saved in the database,
and later parsed and localized for display by AutoCommentFormatter.

The usual general structure of a Wikibase edit summary looks as follows:

```
/* key:scount|lang|carg1|carg2 */ sarg1, sarg2
```

The entire part in `/* ... */` is called the **autocomment**,
and the Wikibase-generated part after it is called the **autosummary**.
Any custom user-provided summary is appended to the autosummary after a comma,
and cannot be distinguished from the autosummary after the edit has been saved.
The autocomment and autosummary are further divided as follows:

- **key** is the message key for translation.
  The full message key used is “wikibase-<i>entitytype</i>-summary-<i>key</i>”,
  where *entitytype* is the Entity type of the Entity being edited and *key* is the key in the summary.
  The *entitytype* falls back to `entity` if no message for the specific Entity type exists;
  thus, the *key* `wbcreate-new` used on an Item will use the message `wikibase-item-summary-wbcreate-new`,
  while the *key* `wbeditentity` used on an Item will use the message `wikibase-entity-summary-wbeditentity`,
  because the more specific message `wikibase-item-summary-wbeditentity` does not exist.
  The *key* is often related to the API module or ChangeOp used to make the edit.
- **scount** is the number of autosummary arguments (see *sargs* below).
  It is available in the message as $1, and used for {{PLURAL}} (“added alias” or “added aliases”).
- **lang** is the language or site ID of the change.
  It is available in the message as $2 (“edited \[en\] label”, “added link to \[enwiki\]”).
  If not applicable to the edit (e.g. statement changes have no language or site ID), this is empty.
- **cargs** are autocomment arguments.
  They are available in the message as $3, $4, etc.
- **sargs** are autosummary arguments.
  They are just plain text, and not used in the translation, but shown after the message.
  Generally, the bulk of the user input goes here, not in the comment arguments:
  for example, label/description/alias texts, sitelink titles, statement values etc. are usually summary arguments.

Note that the roles of *scount*, *lang* and *cargs* are not always guaranteed
(especially for older edit summaries), and should not be relied on.
All the autocomment parts after the key should be passed into the message
as $1, $2, $3, $4, etc.

Annotated example summary:

```
/* wbsetaliases-add:2|en */ test item alias 1, test item alias 2, add two test item aliases
--------------------------- ------------------------------------  -------------------------
|  ~~~~~~~~~~~~~~~~ ^ __    |                                     |
|  key              | |     |                                     |
|             scount| |     |                                     |
|                 lang|     |                                     |
|autocomment                |autosummary                          |user-provided summary
```

This summary may be localized as:

> Added \[en\] aliases: test item alias 1, test item alias 2, add two test item aliases

Some more examples of magic summaries:
  * <code>/* wbsetsitelink-add:1|site */</code> linktitle
  * <code>/* wbsetsitelink-set:1|site */</code> linktitle
  * <code>/* wbsetsitelink-remove:1|site */</code>
  * <code>/* wbsetlabel-add:1|lang */</code> value
  * <code>/* wbsetdescription-set:1|lang */</code> value
  * <code>/* wbsetaliases-remove:1|lang */</code> values...
  * <code>/* wbeditentity-update: */</code>
  * <code>/* wbeditentity-update:0| */</code>
  * <code>/* wblinktitles-create:2| */</code> fromSite:fromPage, toSite:toPage
  * <code>/* wbremoveclaims:n */</code> props...
