<?php

/**
 * This file assigns the default values to all Wikibase Repo settings.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 */

return call_user_func( function() {
	global $wgSquidMaxage;

	$defaults = array(
		'idBlacklist' => array(
			1,
			23,
			42,
			1337,
			9001,
			31337,
			720101010,
		),

		'entityNamespaces' => array(),

		// Define constraints for multilingual terms (such as labels, descriptions and aliases).
		'multilang-limits' => array(
			'length' => 250, // length constraint
		),

		// URL schemes allowed for URL values. See UrlSchemeValidators for a full list.
		'urlSchemes' => array( 'ftp', 'http', 'https', 'irc', 'mailto' ),

		// Items allowed to be used as badges pointing to their CSS class names
		'badgeItems' => array(),

		// List of image property id strings, in order of preference, that should be considered for
		// the "page_image" page property.
		'preferredPageImagesProperties' => array(),

		// Number of seconds for which data output shall be cached.
		// Note: keep that low, because such caches cannot always be purged easily.
		'dataSquidMaxage' => $wgSquidMaxage,

		// Settings for change dispatching
		'dispatchBatchChunkFactor' => 3,
		'dispatchBatchCacheFactor' => 3,

		// Formats that shall be available via SpecialEntityData.
		// The first format will be used as the default.
		// This is a whitelist, some formats may not be supported because when missing
		// optional dependencies (e.g. easyRdf).
		// The formats are given using logical names as used by EntityDataSerializationService.
		'entityDataFormats' => array(
			// using the API
			'json', // default
			'php',

			// using easyRdf
			'rdfxml',
			'n3',
			'turtle',
			'ntriples',

			// hardcoded internal handling
			'html',
		),

		'dataRightsUrl' => function() {
			return $GLOBALS['wgRightsUrl'];
		},

		'dataRightsText' => function() {
			return $GLOBALS['wgRightsText'];
		},

		'transformLegacyFormatOnExport' => true,

		'conceptBaseUri' => function() {
			$uri = $GLOBALS['wgServer'];
			$uri = preg_replace( '!^//!', 'http://', $uri );
			$uri = $uri . '/entity/';

			return $uri;
		},

		// Property used as formatter to link identifiers
		'formatterUrlProperty' => null,

		// Determines how subscription lookup is handled. Possible values:
		//
		// - 'sitelinks': Use only sitelinks to determine which wiki is subscribed to which entity.
		//                Use this mode if the wb_changes_subscription table does not exist.
		// - 'subscriptions': use explicit subscriptions in the wb_changes_subscription table.
		// - 'subscriptions+sitelinks': use a combination of both.
		//
		// @note: if Wikibase Repo and Client are enabled on the same wiki, this setting
		//        needs to match the useLegacyChangesSubscription value in the client settings.
		'subscriptionLookupMode' => 'subscriptions',

		'allowEntityImport' => false,

		// Prefix to use for cache keys that should be shared among a Wikibase Repo instance
		// and all its clients. This is for things like caching entity blobs in memcached.
		//
		// The default setting assumes Wikibase Repo + Client installed together on the same wiki.
		// For a multiwiki / wikifarm setup, to configure shared caches between clients and repo,
		// this needs to be set to the same value in both client and repo wiki settings.
		//
		// For Wikidata production, we set it to 'wikibase-shared/wikidata_1_25wmf24-wikidatawiki',
		// which is 'wikibase_shared/' + deployment branch name + '-' + repo database name,
		// and have it set in both $wgWBClientSettings and $wgWBRepoSettings.
		//
		// Please note that $wgWBClientSettings overrides settings such as this one in the repo,
		// if client is enabled on the same wiki.
		'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ) . '-' . $GLOBALS['wgDBname'],

		// The duration of the object cache, in seconds.
		//
		// As with sharedCacheKeyPrefix, this is both client and repo setting. On a multiwiki
		// setup, this should be set to the same value in both the repo and clients.
		// Also note that the setting value in $wgWBClientSettings overrides the one here.
		'sharedCacheDuration' => 60 * 60,

		// The type of object cache to use. Use CACHE_XXX constants.
		// This is both a repo and client setting, and should be set to the same value in
		// repo and clients for multiwiki setups.
		'sharedCacheType' => $GLOBALS['wgMainCacheType'],

		// Special non-canonical languages and their BCP 47 mappings
		// Based on: https://meta.wikimedia.org/wiki/Special_language_codes
		'canonicalLanguageCodes' => array(
				'simple'      => 'en-x-simple',
				'crh'         => 'crh-Latn',
				'cbk-zam'     => 'cbk-x-zam',
				'map-bms'     => 'jv-x-bms',
				'nrm'         => 'fr-x-nrm',
				'roa-tara'    => 'it-x-tara',
				'de-formal'   => 'de-x-formal',
				'nl-informal' => 'nl-x-informal',
		),

		// List of globe-coordinate properties (listed by id string), in order of preference,
		// to consider for primary coordinates when extracting coordinates from an Entity
		// for the GeoData extension.
		// e.g. array( 'P625', 'P1259' )
		'preferredGeoDataProperties' => array(),

		// Mapping of globe uris to names, as recognized and used by GeoData extension
		// when indexing and querying for coordinates.
		'globeUris' => array(
			'http://www.wikidata.org/entity/Q2' => 'earth',
			'http://www.wikidata.org/entity/Q308' => 'mercury',
			'http://www.wikidata.org/entity/Q313' => 'venus',
			'http://www.wikidata.org/entity/Q405' => 'moon',
			'http://www.wikidata.org/entity/Q111' => 'mars',
			'http://www.wikidata.org/entity/Q7547' => 'phobos',
			'http://www.wikidata.org/entity/Q7548' => 'deimos',
			'http://www.wikidata.org/entity/Q3169' => 'ganymede',
			'http://www.wikidata.org/entity/Q3134' => 'callisto',
			'http://www.wikidata.org/entity/Q3123' => 'io',
			'http://www.wikidata.org/entity/Q3143' => 'europa',
			'http://www.wikidata.org/entity/Q15034' => 'mimas',
			'http://www.wikidata.org/entity/Q3303' => 'enceladus',
			'http://www.wikidata.org/entity/Q15047' => 'tethys',
			'http://www.wikidata.org/entity/Q15040' => 'dione',
			'http://www.wikidata.org/entity/Q15050' => 'rhea',
			'http://www.wikidata.org/entity/Q2565' => 'titan',
			'http://www.wikidata.org/entity/Q15037' => 'hyperion',
			'http://www.wikidata.org/entity/Q17958' => 'iapetus',
			'http://www.wikidata.org/entity/Q17975' => 'phoebe',
			'http://www.wikidata.org/entity/Q3352' => 'miranda',
			'http://www.wikidata.org/entity/Q3343' => 'ariel',
			'http://www.wikidata.org/entity/Q3338' => 'umbriel',
			'http://www.wikidata.org/entity/Q3322' => 'titania',
			'http://www.wikidata.org/entity/Q3332' => 'oberon',
			'http://www.wikidata.org/entity/Q3359' => 'triton',
			'http://www.wikidata.org/entity/Q339' => 'pluto'
		),
	);

	return $defaults;
} );
