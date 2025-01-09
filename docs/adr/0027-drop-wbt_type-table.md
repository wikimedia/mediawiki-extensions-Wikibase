# 27) Drop the wbt_type table {#adr_0027}

Date: 2025-01-09

## Status

accepted

## Context

We're currently working on a way to enable keeping terms-related database tables in a separate server/cluster than the rest of Mediawiki/Wikibase tables ([T351802](https://phabricator.wikimedia.org/T351802)). The preferred solution is to define a [virtual domain](https://www.mediawiki.org/wiki/Manual:$wgVirtualDomainsMapping) within Wikibase for these tables which can then be configured accordingly for Wikidata.

One obstacle is that NameTableStore, which Wikibase uses [in DatabaseTypeIdsStore](https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/f440a01e0d5b4b414b4751f85b735eec65c326b9/lib/includes/Store/Sql/Terms/DatabaseTypeIdsStore.php#28), does not work with virtual domains yet. DatabaseTypeIdsStore is essentially a wrapper around the `wbt_type` table, the contents of which look like this:
```
+--------+-------------+
| wby_id | wby_name    |
+--------+-------------+
|      1 | label       |
|      2 | description |
|      3 | alias       |
+--------+-------------+
```
(the exact mapping might vary)

## Decision

We will drop the `wbt_type` table. This not only unblocks our current work, but also allows us to remove code (TypeIdsAcquirer, TypeIdsResolver, TypeIdsLookup, and implementations) that has only managed these three entries since its introduction over 5 years ago. If we intend to add new term types in the future, we could easily re-introduce this mechanism or find a different solution.

## Consequences

We will hard-code the type ID mapping in its most common form (`[ 'label' => 1, 'description' => 2, 'alias' => 3 ]`) instead of retrieving it from the db table. Most Wikibases, including Wikidata and its test systems, already have this exact mapping, so no migration is needed. Wikibases that have a different mapping will need to migrate to the IDs above via the regular update.php process.

The migration involves updating every row in the `wbt_term_in_lang` table, which contains one entry for every label, description and alias. In order to avoid getting the IDs mixed up, the IDs will first be mapped to temporary values. This means that in the worst case where all three term type IDs diverge from the desired ones, every row in the `wbt_term_in_lang` table will be updated twice.

On Wikibase Cloud, the maximum number of rows in the `wbt_term_in_lang` table across all Wikibases is 1,053,791. Migrating this amount of rows on a not particularly powerful development laptop took ~20s, so the migration time should be manageable.

We considered introducing a config setting for the type ID mapping to avoid the need for a time-consuming migration, but decided against it for now. The migration is a one-off effort, whereas the config would be permanent, and a potential source of problems if not configured correctly.
