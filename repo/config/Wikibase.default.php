<?php

/**
 * This file assigns the default values to all Wikibase Repo settings.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
 *
 * @license GPL-2.0-or-later
 */

return [
	'idBlacklist' => [],

	// List of supported entity types, mapping entity type identifiers to namespace IDs.
	// This setting is used to enable entity types.
	'entityNamespaces' => [],

	// List of entity types that (temporarily) can not be changed; identifiers per EntityDocument::getType()
	'readOnlyEntityTypes' => [],

	// See StatementGrouperBuilder for an example.
	'statementSections' => [],

	// Define constraints for multilingual terms (such as labels, descriptions and aliases).
	'multilang-limits' => [
		'length' => 250, // length constraint
	],

	// URL schemes allowed for URL values. See UrlSchemeValidators for a full list.
	'urlSchemes' => [ 'bzr', 'cvs', 'ftp', 'git', 'http', 'https', 'irc', 'mailto', 'ssh', 'svn' ],

	// Items allowed to be used as badges pointing to their CSS class names
	'badgeItems' => [],

	// Number of seconds for which data output shall be cached.
	// Note: keep that low, because such caches cannot always be purged easily.
	'dataSquidMaxage' => $GLOBALS['wgSquidMaxage'],

	// list of logical database names of local client wikis.
	// may contain mappings from site-id to db-name.
	'localClientDatabases' => [],

	// Settings for change dispatching
	'dispatchMaxTime' => 60 * 60,
	'dispatchIdleDelay' => 10,
	'dispatchBatchChunkFactor' => 3,
	'dispatchBatchCacheFactor' => 3,
	'dispatchDefaultBatchSize' => 1000,
	'dispatchDefaultMaxChunks' => 15,
	'dispatchDefaultDispatchInterval' => 60,
	'dispatchDefaultDispatchRandomness' => 15,

	// Formats that shall be available via SpecialEntityData.
	// The first format will be used as the default.
	// This is a whitelist, some formats may not be supported because when missing
	// optional dependencies (e.g. purtle).
	// The formats are given using logical names as used by EntityDataSerializationService.
	'entityDataFormats' => [
		// using the API
		'json', // default
		'php',

		// using purtle
		'rdfxml',
		'n3',
		'turtle',
		'ntriples',

		// hardcoded internal handling
		'html',
	],

	'dataRightsUrl' => function() {
		return $GLOBALS['wgRightsUrl'];
	},

	'dataRightsText' => function() {
		return $GLOBALS['wgRightsText'];
	},

	'sparqlEndpoint' => null,

	'transformLegacyFormatOnExport' => true,

	'conceptBaseUri' => function() {
		$uri = preg_replace( '!^//!', 'http://', $GLOBALS['wgServer'] );
		return $uri . '/entity/';
	},

	// Property used as formatter to link identifiers
	'formatterUrlProperty' => null,

	// Property used as formatter to link identifiers in JSON/RDF
	'canonicalUriProperty' => null,

	'allowEntityImport' => false,

	/**
	 * Prefix to use for cache keys that should be shared among a Wikibase Repo instance and all
	 * its clients. This is for things like caching entity blobs in memcached.
	 *
	 * The default setting assumes Wikibase Repo + Client installed together on the same wiki.
	 * For a multiwiki / wikifarm setup, to configure shared caches between clients and repo,
	 * this needs to be set to the same value in both client and repo wiki settings.
	 *
	 * For Wikidata production, we set it to 'wikibase-shared/wikidata_1_25wmf24-wikidatawiki',
	 * which is 'wikibase_shared/' + deployment branch name + '-' + repo database name, and have
	 * it set in both $wgWBClientSettings and $wgWBRepoSettings.
	 *
	 * Please note that $wgWBClientSettings overrides settings such as this one in the repo, if
	 * client is enabled on the same wiki.
	 */
	'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ) . '-' . $GLOBALS['wgDBname'],

	/**
	 * The duration of the object cache, in seconds.
	 *
	 * As with sharedCacheKeyPrefix, this is both client and repo setting. On a multiwiki setup,
	 * this should be set to the same value in both the repo and clients. Also note that the
	 * setting value in $wgWBClientSettings overrides the one here.
	 */
	'sharedCacheDuration' => 60 * 60,

	// The type of object cache to use. Use CACHE_XXX constants.
	// This is both a repo and client setting, and should be set to the same value in
	// repo and clients for multiwiki setups.
	'sharedCacheType' => $GLOBALS['wgMainCacheType'],

	/**
	 * List of data types (by data type id) not enabled on the wiki.
	 * This setting is intended to aid with deployment of new data types
	 * or on new Wikibase installs without items and properties yet.
	 *
	 * This setting should be consistent with the corresponding setting on the client.
	 *
	 * WARNING: Disabling a data type after it is in use is dangerous
	 * and might break items.
	 */
	'disabledDataTypes' => [],

	// Special non-canonical languages and their BCP 47 mappings
	// Based on: https://meta.wikimedia.org/wiki/Special_language_codes
	'canonicalLanguageCodes' => [
			'simple'      => 'en-simple',
			'crh'         => 'crh-Latn',
			'cbk-zam'     => 'cbk-x-zam',
			'map-bms'     => 'jv-x-bms',
			'nrm'         => 'fr-x-nrm',
			'roa-tara'    => 'it-x-tara',
			'de-formal'   => 'de-x-formal',
			'nl-informal' => 'nl-x-informal',
	],

	// List of image property id strings, in order of preference, that should be considered for
	// the "page_image" page property.
	'preferredPageImagesProperties' => [],

	// List of globe-coordinate property id strings, in order of preference, to consider for
	// primary coordinates when extracting coordinates from an entity for the GeoData extension.
	'preferredGeoDataProperties' => [],

	// Mapping of globe URIs to canonical names, as recognized and used by GeoData extension
	// when indexing and querying for coordinates.
	'globeUris' => [
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
	],

	// Map between page properties and Wikibase predicates
	// Maps from database property name to array:
	// name => RDF property name (will be prefixed by wikibase:)
	// type => type to convert to (optional)
	'pagePropertiesRdf' => [
		'wb-sitelinks' => [ 'name' => 'sitelinks', 'type' => 'integer' ],
		'wb-claims' => [ 'name' => 'statements', 'type' => 'integer' ],
		'wb-identifiers' => [ 'name' => 'identifiers', 'type' => 'integer' ],
	],

	// Map of foreign repository names to repository-specific settings such as "supportedEntityTypes"
	'foreignRepositories' => [],

	// List of entity types for Special:EntitiesWithoutLabel and …Description, or null for all.
	'supportedEntityTypesForEntitiesWithoutTermListings' => null,

	// URL of geo shape storage API endpoint
	'geoShapeStorageApiEndpointUrl' => 'https://commons.wikimedia.org/w/api.php',

	// Base URL of geo shape storage frontend. Used primarily to build links to the geo shapes. Will
	// be concatenated with the page title, so should end with "/" or "title=". Special characters
	// (e.g. space, percent, etc.) should NOT be encoded.
	'geoShapeStorageBaseUrl' => 'https://commons.wikimedia.org/wiki/',

	// URL of tabular data storage API endpoint
	'tabularDataStorageApiEndpointUrl' => 'https://commons.wikimedia.org/w/api.php',

	// Base URL of tabular data storage frontend. Used primarily to build links to the tabular data
	// pages. Will be concatenated with the page title, so should end with "/" or "title=". Special
	// characters (e.g. space, percent, etc.) should NOT be encoded.
	'tabularDataStorageBaseUrl' => 'https://commons.wikimedia.org/wiki/',

	// Name of the lock manager for dispatch changes coordinator
	'dispatchingLockManager' => null,

	// Configurations for searching entities
	'entitySearch' => [
		// Use CirrusSearch (ElasticSearch) for searching
		'useCirrus' => false,
		// Default label scoring profile name, for prefix search
		// See profiles in config/EntityPrefixSearchProfiles.php
		'prefixSearchProfile' => 'default',
		// Default profile name for fulltext search
		// See profiles in config/EntitySearchProfiles.php
		'fulltextSearchProfile' => 'wikibase',
		// Custom prefix query builder profiles placeholder
		// Field weight profiles. These profiles specify relative weights
		// of label fields for different languages, e.g. exact language match
		// vs. fallback language match.
		// (See config/EntityPrefixSearchProfiles.php)
		'prefixSearchProfiles' => [],
		// Profile definitions for fulltext search.
		// Note that these will be merged with Cirrus standard profiles,
		// so namespacing is recommended.
		'fulltextSearchProfiles' => [],
		// Default rescore profile for prefix search
		'defaultPrefixRescoreProfile' => 'wikibase_prefix',
		// Default rescore profile for prefix search
		'defaultFulltextRescoreProfile' => 'wikibase_prefix',
		// Custom rescore profiles placeholder
		// (See config/ElasticSearchRescoreProfiles.php)
		'rescoreProfiles' => [],
		// Custom function chains placeholder
		// (See config/ElasticSearchRescoreFunctions.php)
		'rescoreFunctionChains' => [],
		// Type (de)boosts for rescoring functions
		'statementBoost' => [],
		// List of languages that we want to have stemming analyzers
		// 'index' means we generate stemmed field on index
		// 'query' means we use it on query
		// See https://phabricator.wikimedia.org/T180169 for discussion.
		'useStemming' => [],
	],

	// List of properties to be indexed
	'searchIndexProperties' => [],
	// List of property types to be indexed
	'searchIndexTypes' => [],
	// List of properties to be excluded from indexing
	'searchIndexPropertiesExclude' => [],
	// List of properties that, if in a qualifier, will be used for indexing quantities
	'searchIndexQualifierPropertiesForQuantity' => [],

	// List of entity types that rdf export is disabled
	'disabledRdfExportEntityTypes' => [],

	// Use search-related fields of wb_terms table
	'useTermsTableSearchFields' => true,

	// Override useTermsTableSearchFields for writing
	'forceWriteTermsTableSearchFields' => false,

	// Change it to a positive number so it becomes effective
	'dispatchLagToMaxLagFactor' => 0,

	// DB group to use in dump maintenance scripts. Defaults to "dump", per T147169.
	'dumpDBDefaultGroup' => 'dump',

	/**
	 * Upper inclusive range bound of Q-ID. Html links for Item IDs that within this range will
	 * be rendered using ItemIdHtmlLinkFormatter.
	 *
	 * @note This parameter is added solely for Wikidata transition use-case and is temporary.
	 *       Should be removed not later than 31-09-2018. Do not use it.
	 *
	 * @var int
	 * @see https://phabricator.wikimedia.org/T196882
	 * @see \Wikibase\Lib\Formatters\ControlledFallbackEntityIdFormatter
	 * @see \Wikibase\Lib\Formatters\ItemIdHtmlLinkFormatter
	 */
	'tmpMaxItemIdForNewItemIdHtmlFormatter' => 0,

];
