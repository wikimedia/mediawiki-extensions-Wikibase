<?php

use Wikibase\EntityChange;
use Wikibase\ItemChange;

/**
 * Lib is not an application and should not have global config state.
 * Therefore you should NOT add new settings here. If both client and repo
 * need them, add them to both.
 *
 * This file assigns the default values to all Wikibase Lib settings.
 *
 * This file is NOT an entry point the WikibaseLib extension. Use WikibaseLib.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 */
return [
	// whether changes get recorded to wb_changes
	'useChangesTable' => true,

	'siteLinkGroups' => [
		'wikipedia',
	],

	'specialSiteLinkGroups' => [],

	// local by default. Set to something LBFactory understands.
	'changesDatabase' => false,

	// list of logical database names of local client wikis.
	// may contain mappings from site-id to db-name.
	'localClientDatabases' => [],

	'changeHandlers' => [
		'wikibase-item~add' => ItemChange::class,
		'wikibase-property~add' => EntityChange::class,
		'wikibase-query~add' => EntityChange::class,

		'wikibase-item~update' => ItemChange::class,
		'wikibase-property~update' => EntityChange::class,
		'wikibase-query~update' => EntityChange::class,

		'wikibase-item~remove' => ItemChange::class,
		'wikibase-property~remove' => EntityChange::class,
		'wikibase-query~remove' => EntityChange::class,

		'wikibase-item~refresh' => ItemChange::class,
		'wikibase-property~refresh' => EntityChange::class,
		'wikibase-query~refresh' => EntityChange::class,

		'wikibase-item~restore' => ItemChange::class,
		'wikibase-property~restore' => EntityChange::class,
		'wikibase-query~restore' => EntityChange::class,
	],

	'maxSerializedEntitySize' => $GLOBALS['wgMaxArticleSize'],
];
