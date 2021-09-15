# Change propagation

**Change propagation** or **dispatching** is the process of updating Client sites with Repo changes / edits.
This allows clients to update pages quickly after information on the repository changed.

**NOTE:**
 - Change propagation is only possible with direct database access between the wikis (that is, inside a “wiki farm”).
 - Change propagation does not support federation (change propagation between repositories) nor does it support multi-repository setups on the client wiki.

Change propagation requires several components to work together.

On the Repo, we need:

* Subscription management, so the repository knows which client wiki is interested in changes to which entities.
* Dispatch state, so the repository knows which changes have already been dispatched to which client.
* A buffer of the changes themselves.
* Access to each client's job queue, to push [ChangeNotificationJob]s to.

On each Client, there needs to be:

* Usage tracking (see @ref md_docs_topics_usagetracking).
* Access to sitelinks stored in the repository.
* [ChangeHandler] for processing changes on the repo, triggered by [ChangeNotificationJob]s being executed.
* [AffectedPagesFinder], a mechanism to determine which pages are affected by which change, based on usage tracking information (see @ref md_docs_topics_usagetracking).
* [WikiPageUpdater], for updating the client wiki's state.

### On the Repo

\msc
  width="1000";

  "Wikibase Repo",
  "wb_changes",
  "wb_changes_dispatch",
  dispatchChanges,
  LockMechanism,
  "Wikibase Client",
  pruneChanges;

  "Wikibase Repo" rbox "Wikibase Repo" [label="Wikibase Web Requets", textbgcolour=red],
  dispatchChanges rbox dispatchChanges [label="CronX", textbgcolour=aqua],
  pruneChanges rbox pruneChanges [label="CronX", textbgcolour=green];

  "Wikibase Repo" -> "wb_changes" [label="Edit Recorded", linecolour=red],

  dispatchChanges => "wb_changes_dispatch" [label="Find Clients in need of dispatching", linecolour=blue];
  dispatchChanges << "wb_changes_dispatch" [label="Client Wikis", linecolour=blue];
  dispatchChanges => LockMechanism [label="Lock Wiki X", linecolour=blue];
  dispatchChanges << LockMechanism [label="Lock Successful", linecolour=blue];
  dispatchChanges => "wb_changes" [label="Find changes to dispatch for Wiki X", linecolour=blue];
  dispatchChanges << "wb_changes" [label="Changes for Wiki X", linecolour=blue];
  dispatchChanges => "wb_changes_dispatch" [label="Update dispatch states", linecolour=blue];
  dispatchChanges => "Wikibase Client" [label="Schedule ChangeNotificationJob", linecolour=blue];

  pruneChanges => "wb_changes_dispatch" [label="Find oldest change needed", linecolour=green];
  pruneChanges << "wb_changes_dispatch" [label="Oldest change needed", linecolour=green];
  pruneChanges => "wb_changes" [label="Prune changes before X", linecolour=green];

\endmsc

The basic operation of change dispatching involves running two scripts regularly, typically as cron jobs: [dispatchChanges.php] and [pruneChanges.php], both located in the repo/maintenance/ directory.
A typical cron setup could look like this:

* Every minute, run [dispatchChanges.php] --max-time 120
* Every hour, run [pruneChanges.php] --keep-hours 3 --grace-minutes 20
* Every minute, run runJobs.php on all clients.

The --max-time 120 parameters tells [dispatchChanges.php] to be active for at most two minutes. --grace-minutes 20 tells [pruneChanges.php] to keep changes for at least 20 minutes after they have been dispatched.
This allows the client side job queue to lag for up to 20 minutes before problems arise.

Note that multiple instances of [dispatchChanges.php] can run at the same time.
They are designed to automatically coordinate. For details, refer to the --help output of these maintenance scripts.

**Usage Tracking and Subscription Management**

Usage tracking and subscription management are described in detail in @ref md_docs_topics_usagetracking.

**Change Buffer**

The change buffer holds information about each change, stored in the [wb_changes] table, to be accessed by the client wikis when processing the respective change.

**Dispatch State**

Dispatch state is managed by a [ChangeDispatchCoordinator] service.
The default implementation is based on the [wb_changes_dispatch] table.

Per default, global MySQL locks are used to ensure that only one process can dispatch to any given client wiki at a time.

**dispatchChanges.php script**

The dispatchChanges script notifies client wikis of changes on the repository.
It reads information from the [wb_changes] and [wb_changes_dispatch] tables, and posts [ChangeNotificationJob]s to the clients' job queues.

The basic scheduling algorithm is as follows: for each client wiki, define how many changes they have not yet seen according to [wb_changes_dispatch] (we refer to that number as “dispatch lag”).
Find the ''n'' client wikis that have the most lag (and have not been touched for some minimal delay).
Pick one of these wikis at random. For the selected target wiki, find changes it has not yet seen to entities it is subscribed to, up to some maximum number of m changes.
Construct a [ChangeNotificationJob] event containing the IDs of these changes, and push it to the target wiki's JobQueue.
In [wb_changes_dispatch], record all changes touched in this process as seen by the target wiki.

The [dispatchChanges.php] is designed to be safe against concurrent execution.
It can be scaled easily by simply running more instances in parallel.
The locking mechanism used to prevent race conditions can be configured using the [dispatchingLockManager] setting.
Per default, named locks on the repo database are used.
Redis based locks are supported as an alternative and use on wikidata.org

**dispatch lag**

Dispatch lag is linked to max lag.
If dispatch lag increases, max lag will also increase.
Dispatching is not as efficient as DB replication so the raw lag value is not used, instead a factor is applied.
The factor is configurable using the [dispatchLagToMaxLagFactor] setting.

For more about maxlag see https://www.mediawiki.org/wiki/Manual:Maxlag_parameter

### On Clients

**SiteLinkLookup**

A [SiteLinkLookup] allows the client wiki to determine which local pages are “connected” to a given Item on the repository.
Each client wiki can access the repo's sitelink information via a [SiteLinkLookup] service returned by [ClientStore::getSiteLinkLookup()].
This information is stored in the [wb_items_per_site] table in the repo's database.

**ChangeHandler**

The [ChangeHandler::handleChanges()] method gets called with a list of changes loaded by a [ChangeNotificationJob]s.
A [ChangeRunCoalescer] is then used to merge consecutive changes by the same user to the same entity, reducing the number of logical events to be processed on the client, and to be presented to the user.

ChangeHandler will then for each change determine the affected pages using the [AffectedPagesFinder], which uses information from the wbc_entity_usage table (see @ref md_docs_topics_usagetracking).
It then uses a [WikiPageUpdater] to update the client wiki's state: rows are injected into the recentchanges database table, pages using the affected entity's data are re-parsed, and the web cache for these pages is purged.

**WikiPageUpdater**

The [WikiPageUpdater] class defines three methods for updating the client wikis state according to a given change on the repository:

* [WikiPageUpdater::scheduleRefreshLinks()]
  * Will re-parse each affected page, allowing the link tables to be updated appropriately. This is done asynchronously using RefreshLinksJobs. No batching is applied, since RefreshLinksJobs are slow and this benefit more from deduplication than from batching.
* [WikiPageUpdater::purgeWebCache()]
  * Will update the web-cache for each affected page. This is done asynchronously in batches, using HTMLCacheUpdateJob. The batch size is controlled by the [purgeCacheBatchSize] setting.
* [WikiPageUpdater::injectRCRecords()]
  * Will create a RecentChange entry for each affected page. This is done asynchronously in batches, using [InjectRCRecordsJob]s. The batch size is controlled by the [recentChangesBatchSize] setting.

[dispatchChanges.php]: @ref dispatchChanges.php
[pruneChanges.php]: @ref pruneChanges.php
[AffectedPagesFinder]: @ref Wikibase::Client::Changes::AffectedPagesFinder
[ChangeHandler]: @ref Wikibase::Client::Changes::ChangeHandler
[ChangeHandler::handleChanges()]: @ref Wikibase::Client::Changes::ChangeHandler::handleChanges()
[WikiPageUpdater]: @ref Wikibase::Client::Changes::WikiPageUpdater
[WikiPageUpdater::scheduleRefreshLinks()]: @ref Wikibase::Client::Changes::WikiPageUpdater::scheduleRefreshLinks()
[WikiPageUpdater::purgeWebCache()]: @ref Wikibase::Client::Changes::WikiPageUpdater::purgeWebCache()
[WikiPageUpdater::injectRCRecords()]: @ref Wikibase::Client::Changes::WikiPageUpdater::injectRCRecords()
[ChangeRunCoalescer]: @ref Wikibase::Client::Changes::ChangeRunCoalescer
[InjectRCRecordsJob]: @ref Wikibase::Client::Changes::InjectRCRecordsJobpurgeCacheBatchSize
[ChangeNotificationJob]: @ref Wikibase::Client::ChangeNotificationJob
[ClientStore::getSiteLinkLookup()]: @ref Wikibase::Client::Store::ClientStore::getSiteLinkLookup()
[SiteLinkLookup]: @ref Wikibase::Lib::Store::SiteLinkLookup
[ChangeDispatchCoordinator]: @ref Wikibase::Store::ChangeDispatchCoordinator
[wb_changes]: @ref md_docs_sql_wb_changes
[wb_changes_dispatch]: @ref md_docs_sql_wb_changes_dispatch
[wb_items_per_site]: @ref md_docs_sql_wb_items_per_site
[purgeCacheBatchSize]: @ref client_purgeCacheBatchSize
[recentChangesBatchSize]: @ref client_recentChangesBatchSize
[dispatchLagToMaxLagFactor]: @ref repo_dispatchLagToMaxLagFactor
[dispatchingLockManager]: @ref repo_dispatchingLockManager
