# Change propagation

**Change propagation** or **dispatching** is the process of updating Client sites with Repo changes / edits.
This allows clients to update pages quickly after information on the repository changed.

**NOTE:**
 - Change propagation is only possible with direct database access between the wikis (that is, inside a “wiki farm”).
 - Change propagation does not support federation (change propagation between repositories) nor does it support multi-repository setups on the client wiki.

Change propagation requires several components to work together.

On the Repo, we need:

* Subscription management, so the repository knows which client wiki is interested in changes to which entities.
* A buffer of the changes themselves.
* Access to each client's job queue, to push [EntityChangeNotificationJob]s to.

On each Client, there needs to be:

* Usage tracking (see @ref docs_topics_usagetracking).
* Access to sitelinks stored in the repository.
* [ChangeHandler] for processing changes on the repo, triggered by [EntityChangeNotificationJob]s being executed.
* [AffectedPagesFinder], a mechanism to determine which pages are affected by which change, based on usage tracking information (see @ref docs_topics_usagetracking).
* [WikiPageUpdater], for updating the client wiki's state.

### On the Repo

The main work on the Repo side of things is done by the [DispatchChangesJob], which is scheduled at the end of the [RecentChangeSaveHookHandler].

\msc
  width="1000";

  "Wikibase Repo",
  "wb_changes",
  "DispatchChanges job",
  "wb_changes_subscription",
  "Wikibase Client";

  "Wikibase Repo" rbox "Wikibase Repo" [label="Wikibase Web Requests", textbgcolour=red];

  "Wikibase Repo" -> "wb_changes" [label="Edit Recorded", linecolour=red];
  "Wikibase Repo" -> "DispatchChanges job" [label="Queue job with Entity id as parameter", linecolour=red];
  "DispatchChanges job" -> "wb_changes" [label="get changes for Entity id"];
  "DispatchChanges job" << "wb_changes" [label="ALL changes for Entity id"];
  "DispatchChanges job" -> "wb_changes_subscription" [label="get wikis subscribed to Entity id"];
  "DispatchChanges job" << "wb_changes_subscription" [label="Client Wikis"];

  "DispatchChanges job" => "Wikibase Client" [label="Schedule EntityChangeNotificationJob with full changes"];
  "DispatchChanges job" -> "wb_changes" [label="Delete changes by Change-Id"];
\endmsc

**Usage Tracking and Subscription Management**

Usage tracking and subscription management are described in detail in @ref docs_topics_usagetracking.

**Change Buffer**

The change buffer holds information about each change, stored in the [wb_changes] table, to be accessed by the repo wiki when processing the respective changes.
### On Clients

**SiteLinkLookup**

A [SiteLinkLookup] allows the client wiki to determine which local pages are “connected” to a given Item on the repository.
Each client wiki can access the repo's sitelink information via a [SiteLinkLookup] service returned by [ClientStore::getSiteLinkLookup()].
This information is stored in the [wb_items_per_site] table in the repo's database.

**ChangeHandler**

The [ChangeHandler::handleChanges()] method gets called with a list of changes provided by a [EntityChangeNotificationJob]s.
A [ChangeRunCoalescer] is then used to merge consecutive changes by the same user to the same entity, reducing the number of logical events to be processed on the client, and to be presented to the user.

ChangeHandler will then for each change determine the affected pages using the [AffectedPagesFinder], which uses information from the wbc_entity_usage table (see @ref docs_topics_usagetracking).
It then uses a [WikiPageUpdater] to update the client wiki's state: rows are injected into the recentchanges database table, pages using the affected entity's data are re-parsed, and the web cache for these pages is purged.

**WikiPageUpdater**

The [WikiPageUpdater] class defines three methods for updating the client wikis state according to a given change on the repository:

* [WikiPageUpdater::scheduleRefreshLinks()]
  * Will re-parse each affected page, allowing the link tables to be updated appropriately. This is done asynchronously using RefreshLinksJobs. No batching is applied, since RefreshLinksJobs are slow and this benefit more from deduplication than from batching.
* [WikiPageUpdater::purgeWebCache()]
  * Will update the web-cache for each affected page. This is done asynchronously in batches, using HTMLCacheUpdateJob. The batch size is controlled by the [purgeCacheBatchSize] setting.
* [WikiPageUpdater::injectRCRecords()]
  * Will create a RecentChange entry for each affected page. This is done asynchronously in batches, using [InjectRCRecordsJob]s. The batch size is controlled by the [recentChangesBatchSize] setting.

[DispatchChangesJob]: @ref Wikibase::Repo::ChangeModification::DispatchChangesJob
[RecentChangeSaveHookHandler]: @ref Wikibase::Repo::Hooks::RecentChangeSaveHookHandler
[AffectedPagesFinder]: @ref Wikibase::Client::Changes::AffectedPagesFinder
[ChangeHandler]: @ref Wikibase::Client::Changes::ChangeHandler
[ChangeHandler::handleChanges()]: @ref Wikibase::Client::Changes::ChangeHandler::handleChanges()
[WikiPageUpdater]: @ref Wikibase::Client::Changes::WikiPageUpdater
[WikiPageUpdater::scheduleRefreshLinks()]: @ref Wikibase::Client::Changes::WikiPageUpdater::scheduleRefreshLinks()
[WikiPageUpdater::purgeWebCache()]: @ref Wikibase::Client::Changes::WikiPageUpdater::purgeWebCache()
[WikiPageUpdater::injectRCRecords()]: @ref Wikibase::Client::Changes::WikiPageUpdater::injectRCRecords()
[ChangeRunCoalescer]: @ref Wikibase::Client::Changes::ChangeRunCoalescer
[InjectRCRecordsJob]: @ref Wikibase::Client::Changes::InjectRCRecordsJobpurgeCacheBatchSize
[EntityChangeNotificationJob]: @ref Wikibase::Client::EntityChangeNotificationJob
[ClientStore::getSiteLinkLookup()]: @ref Wikibase::Client::Store::ClientStore::getSiteLinkLookup()
[SiteLinkLookup]: @ref Wikibase::Lib::Store::SiteLinkLookup
[wb_changes]: @ref docs_sql_wb_changes
[wb_items_per_site]: @ref docs_sql_wb_items_per_site
[purgeCacheBatchSize]: @ref client_purgeCacheBatchSize
[recentChangesBatchSize]: @ref client_recentChangesBatchSize
