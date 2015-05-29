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

$wgWBLibDefaultSettings = array(

	// whether changes get recorded to wb_changes
	'useChangesTable' => true,

	'entityPrefixes' => array(
		'q' => 'item',
		'p' => 'property',
	),

	'siteLinkGroups' => array(
		'wikipedia',
	),

	'specialSiteLinkGroups' => array(),

	// local by default. Set to something LBFactory understands.
	'changesDatabase' => false,

	// list of logical database names of local client wikis.
	// may contain mappings from site-id to db-name.
	'localClientDatabases' => array(),

	'dispatchBatchChunkFactor' => 3,
	'dispatchBatchCacheFactor' => 3,

	// Allow the TermIndex table to work without weights,
	// for sites that cannot easily roll out schema changes on large tables.
	// This means that all searches will return an undefined order
	// (depending on the database's inner working).
	'withoutTermWeight' => false,

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

	'dataTypes' => array(
		'commonsMedia',
		'globe-coordinate',
		'quantity',
		'monolingualtext',
		'string',
		'time',
		'url',
		'wikibase-item',
		'wikibase-property',
	),

	// URL schemes allowed for URL values. See UrlSchemeValidators for a full list.
	'urlSchemes' => array( 'ftp', 'http', 'https', 'irc', 'mailto' )
);

// experimental stuff
if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
	// experimental data types
	$wgWBLibDefaultSettings['dataTypes'] = array_merge(
		$wgWBLibDefaultSettings['dataTypes'],
		array() //'multilingual-text'
	);
}

return $wgWBLibDefaultSettings;
