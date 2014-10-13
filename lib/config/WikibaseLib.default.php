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

return call_user_func( function() {

	$defaults = array(

		// whether changes get recorded to wb_changes
		'useChangesTable' => true,

		// whether property meta data is available in wb_property_info
		'usePropertyInfoTable' => true,

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

		// Prefix to use for cache keys that should be shared among
		// a wikibase repo and all its clients.
		// The default includes WBL_VERSION and $wgDBname;
		// In order to share caches between clients (and the repo),
		// set a prefix based on the repo's name and WBL_VERSION
		// or a similar version ID.
		// NOTE: WikibaseClient.default.php overrides this to depend
		// on repoDatabase dynamically.
		'sharedCacheKeyPrefix' => $GLOBALS['wgDBname'] . ':WBL/' . WBL_VERSION,

		// The duration of the object cache, in seconds.
		'sharedCacheDuration' => 60 * 60,

		// The type of object cache to use. Use CACHE_XXX constants.
		'sharedCacheType' => $GLOBALS['wgMainCacheType'],

		'dispatchBatchChunkFactor' => 3,
		'dispatchBatchCacheFactor' => 3,

		// Allow the TermIndex table to work without weights,
		// for sites that can not easily roll out schema changes on large tables.
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
		),

		// URL schemes allowed for values of the URL type.
		// Supported types include 'http', 'https', 'ftp', and 'mailto'.
		'urlSchemes' => array( 'http', 'https', 'ftp' )
	);

	// experimental stuff
	if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
		// experimental data types
		$defaults['dataTypes'] = array_merge( $defaults['dataTypes'], array(
			//'multilingual-text',
		) );
	}

	return $defaults;
} );
