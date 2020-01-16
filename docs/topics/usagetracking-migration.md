# Usagetracking migration

This document describes the deployment process necessary for migrating from “naive” sitelink based dispatching and purging to the full usage tracking needed to support arbitrary access to entities from wikitext.

Overview of the tables involved (using “repowiki” and “clientwiki” as the database name and site ID for the repo and respective client wiki):

repowiki.wb\_items\_per\_site
The sitelinks stored on the repo, mapping entity IDs to page titles on client wikis.

repowiki.wb\_changes\_subscription
(new) Subscriptions to change notifications, mapping entity IDs to client site ids. (Note that site ids are often, but not necessarily, the same as the client wiki's database name).

clientwiki.wbc\_entity\_usage
(new) Tracks the usage of entities on the client, including the information which page uses which aspect of which entity.

\<-- For deferred deployment of schema changes, usage of the new tables can be disabled using the appropriate feature switches: --\>

Maintenance scripts are used to populate the new database tables used for usage tracking and subscription management:

repo/maintenance/populateChangesSubscription.php
Populates wb\_changes\_subscription table based on repowiki.wb\_items\_per\_site.

client/maintenance/populateEntityUsage.php
Populates the wbc\_entity\_usage table based on repowiki.wb\_items\_per\_site (data transfer from repo to client).

client/maintenance/updateSubscriptions.php
Updates repowiki.wb\_changes\_subscription based on the client wiki's wbc\_entity\_usage table (data transfer from client to repo). This should be run *after* populateChangesSubscription.php and populateEntityUsage.php.

Deployment of the new usage tracking scheme can be done in three steps:

Create subscription table on the repo
-------------------------------------

To set up the subscription tracking table wb\_changes\_subscription on the repo:

-   Deploy the schema change by running update.php.
-   Run repo/maintenance/populateChangesSubscription.php to initialize the wb\_changes\_subscription table based on repowiki.wb\_items\_per\_site.

*NOTE*: If any clients already have usage tracking enabled, then updateSubscriptions.php can also be run and subscription tracking enabled for each of them at this point. See the instructions in section *Start tracking client subscriptions based on entity usage* below.

Start tracking entity usage on the client wikis
-----------------------------------------------

To enable usage tracking on a client wiki:

-   Deploy the schema change by running update.php.
-   Run client/maintenance/populateEntityUsage.php to initialize the wbc\_entity\_usage table based on repowiki.wb\_items\_per\_site.
-   If desired, enable arbitrary access by setting [allowArbitraryDataAccess] = true

Start tracking client subscriptions based on entity usage
---------------------------------------------------------

Client wikis should automatically update their subscription to changes on the repo based on which entities they use. To enable such subscription tracking based on entity usage:

-   Make sure the client has the wbc\_entity\_usage table set up, see above.
-   Make sure the repo has the wb\_changes\_subscription table set up, see above.
-   Run client/maintenance/updateSubscriptions.php to put entries into repowiki.wb\_changes\_subscription based on the client wiki's wbc\_entity\_usage table.
-   If desired, enable arbitrary access by setting [allowArbitraryDataAccess] = true

Start using subscriptions for dispatching
-----------------------------------------

This is not yet implemented, see [T66590](https://phabricator.wikimedia.org/T66590) and [T90755](https://phabricator.wikimedia.org/T90755).

[allowArbitraryDataAccess]: @ref client_allowArbitraryDataAccess
