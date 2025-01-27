# Usage tracking

Tracking happens on two levels:

- The client wiki tracks which pages use (which aspect of) which entity (from which repo).
- Each repo tracks which client uses which entity.

This is used to optimize change notifications on two levels (see @ref docs_topics_change-propagation):

- The repo sends notifications to the clients that use the modified entity in question.
- The client compares incoming notifications with its local tracking table to decide which pages to purge/update.

### Client side usage tracking

Each client wiki tracks which pages use (which aspect of) which entity (from which repo).
The “aspect” is used to decide which kind of change is relevant for the given kind of usage, and what kind of update is needed for the page in question.
Among others the following aspects are defined:

 - **sitelinks**
   - Only an item's sitelinks (including badges) are used.
 - **label**
   - Only the entity's label is used.
   - This would be the case when a localized reference to the entity is shown on a page.
   - It's also used in cases when a property is referenced by label.
   - A page that uses a label should be updated when that label chances, but this kind of update my be considered low priority.
   - The language in which the label is used is tracked along as a “modification” of the label aspect.
   - In case language fallback is applied, all relevant languages are considered to be used on the page.
 - **all**
   - Any and all aspects of the entity may be used on the given page.
   - This includes statements, claims, and labels.
   - This kind of usage triggers a full re-parse on any change to the entity.
   - This aspect of use is recorded when entity data is accessed via Lua or the \#property parser function.

Entity usage on client pages is tracked using the following codes (each representing one aspect):

 - sitelinks (S) - The entity's sitelinks are used.
 - label (L.xx) - The entity's label in language xx is used.
 - description (D.xx) - The entity's description in language xx is used.
 - title (T) - The title of the local page corresponding to the entity is used.
 - statements (C) - Certain statements (identified by their property id) from the entity are used.
 - other (O) - Something else about the entity is used. This currently implies alias usage and entity existence checks.
 - all (X) - All aspects of an entity are or may be used.

Changes result in updates to pages that use the respective entity based on the aspect that is used.
Changes are classified accordingly:

 - sitelinks (S) - Any change to the entity's sitelinks. Pages that use the S or X aspect are updated.
 - label (L.xx) - The label in the language “xx” changed. Pages that use the L.xx or X aspect are updated.
 - title (T) - The sitelink corresponding to the local wiki was changed. Pages that use the S, T, or X aspect are updated.
 - other (O) - Something else about the entity (such as statement data) changed. Only pages that use the O or X aspects are updated.

This way, editing e.g. statements will not cause pages that just show the entities label to be purged.

The database table for tracking client side usage is called [wbc_entity_usage], and can be thought of as a links table, just like templatelinks or imagelinks.

### Updating of usage entries

Usage tracking information on the client has to be updated when pages are edited (resp. created, deleted, or renamed) and when pages are re-rendered due to a change in a template. In addition to that, usages that become apparent only when the page is rendered with a specific target language need to be tracked when such a language specific rendering is committed to the parser cache. This is particularly important for per-language tracking of label usage on multilingual wikis.

Tracking information, that needs to be added as new renderings of a page materialize, is being added to the tracking table, after the corresponding parser cache entry has been saved. Tracking data is being discarded whenever the corresponding parser cache entries are being invalidated. If that happens, we simply remove all records in wbc\_entity\_usage except for the ones in the new (post invalidation) parser cache entry.

This implies that entity usage tracking is actually tracking usage in page renderings (technically, entries in the parser cache). It does not directly correspond to what is or is not present in a pages wikitext or templates. However, since a page is always rendered in at least one language when it is edited, this distinction is somewhat academic.

Overview of events that trigger updates to usage tracking:

 - [LinksUpdateComplete]
   - Add usage entries from ParserOutput
   - Prune all old entries, unsubscribe unused entries
 - [ParserCacheSave]
   - Add new usage entries from ParserOutput
 - [ArticleDeleteComplete]
   - Prune all entries, unsubscribe unused entries

### Usages not stored in the database

There are two kinds of usages that are not stored in the database, but created on-the-fly.
(Their names are admittedly far from ideal,
and could probably be improved to make the difference between them clearer.)

#### Virtual usages

*Virtual usages* are synthesized based on a change to an entity and the diff introduced by that change.
[AffectedPagesFinder] adds virtual usages when an item’s sitelink for the local wiki is edited,
based on the old and new title in the sitelink, so that both get updated.

#### Implicit usages

*Implicit usages* are synthesized based on the “steady state” of the entity data,
not directly related to any change to the entity.
[ImplicitDescriptionUsageLookup] adds implicit usages on the descriptions of items linked to local pages,
so that description edits are added to the recent changes even if the descriptions are not used directly.

### Repo side usage tracking

Each repo tracks which client uses which entity. This is done in the [wb_changes_subscription] table.

This table is updated whenever the client side tracking table is updated.
To do this, the client wiki must, whenever a page is edited, determine which entities are used (in order to record this in the local tracking table), but in addition to this, detect whether an entity that wasn't used previously anywhere on the wiki is now used by the edited page, or whether the edit removed the last usage of any of the entities previously used on the page.

[wb_changes_subscription]: @ref docs_sql_wb_changes_subscription
[wbc_entity_usage]: @ref docs_sql_wbc_entity_usage
[LinksUpdateComplete]: https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
[ParserCacheSave]: https://www.mediawiki.org/wiki/Manual:Hooks/ParserCacheSave
[ArticleDeleteComplete]: https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
[AffectedPagesFinder]: @ref Wikibase::Client::Changes::AffectedPagesFinder
[ImplicitDescriptionUsageLookup]: @ref Wikibase::Client::Usage::ImplicitDescriptionUsageLookup
