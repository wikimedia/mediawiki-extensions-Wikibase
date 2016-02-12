<?php

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
 * @licence GNU GPL v2+
 */

return array(
	// whether changes get recorded to wb_changes
	'useChangesTable' => true,

	'siteLinkGroups' => array(
		'wikipedia',
	),

	'specialSiteLinkGroups' => array(),

	// local by default. Set to something LBFactory understands.
	'changesDatabase' => false,

	// list of logical database names of local client wikis.
	// may contain mappings from site-id to db-name.
	'localClientDatabases' => array(),

	'changeHandlers' => array(
		'wikibase-item~add' => 'Wikibase\ItemChange',
		'wikibase-property~add' => 'Wikibase\EntityChange',
		'wikibase-query~add' => 'Wikibase\EntityChange',

		'wikibase-item~update' => 'Wikibase\ItemChange',
		'wikibase-property~update' => 'Wikibase\EntityChange',
		'wikibase-query~update' => 'Wikibase\EntityChange',

		'wikibase-item~remove' => 'Wikibase\ItemChange',
		'wikibase-property~remove' => 'Wikibase\EntityChange',
		'wikibase-query~remove' => 'Wikibase\EntityChange',

		'wikibase-item~refresh' => 'Wikibase\ItemChange',
		'wikibase-property~refresh' => 'Wikibase\EntityChange',
		'wikibase-query~refresh' => 'Wikibase\EntityChange',

		'wikibase-item~restore' => 'Wikibase\ItemChange',
		'wikibase-property~restore' => 'Wikibase\EntityChange',
		'wikibase-query~restore' => 'Wikibase\EntityChange',
	),

	'maxSerializedEntitySize' => $GLOBALS['wgMaxArticleSize'],
);
