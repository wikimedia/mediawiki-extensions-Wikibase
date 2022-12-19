<?php

/**
 * This file assigns the default values to all Wikibase Repo settings.
 *
 * This file is NOT an entry point the Wikibase extension.
 * It should not be included from outside the extension.
 *
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\SettingsArray;

global $wgCdnMaxAge;

/** @phan-file-suppress PhanUnextractableAnnotation */
return [
	// feature flag for federated properties
	'federatedPropertiesEnabled' => false,

	// url for federated properties source location
	'federatedPropertiesSourceScriptUrl' => 'https://www.wikidata.org/w/',

	// feature flag for tainted references
	'taintedReferencesEnabled' => false,

	// url of (termbox) ssr-server
	'ssrServerUrl' => null,

	// Timeout for SSR-Server in seconds
	'ssrServerTimeout' => 3,

	// feature flag for termbox
	'termboxEnabled' => true,

	// debug flag for termbox ssr
	'termboxUserSpecificSsrEnabled' => true,

	'reservedIds' => [],

	// List of entity types that (temporarily) can not be changed; identifiers per EntityDocument::getType()
	'readOnlyEntityTypes' => [],

	// See StatementGrouperBuilder for an example.
	'statementSections' => [],

	// Define constraints for various strings, such as multilingual terms (such as labels, descriptions and aliases).
	'string-limits' => [
		'multilang' => [
			'length' => 250, // length constraint
		],
		'VT:monolingualtext' => [
			'length' => 400,
		],
		'VT:string' => [
			'length' => 400,
		],
		'PT:url' => [
			'length' => 500,
		],
	],

	// URL schemes allowed for URL values. See UrlSchemeValidators for a full list.
	'urlSchemes' => [ 'bzr', 'cvs', 'ftp', 'git', 'http', 'https', 'irc', 'ircs', 'mailto', 'ssh', 'svn' ],

	// Items allowed to be used as badges pointing to their CSS class names.
	'badgeItems' => [],

	// Item IDs that are redirect badges.
	'redirectBadgeItems' => [],

	// Number of seconds for which data output on Special:EntityData should be cached.
	// Note: keep that low, because such caches cannot always be purged easily.
	'dataCdnMaxAge' => $wgCdnMaxAge,

	// list of logical database names of local client wikis.
	// may contain mappings from site-id to db-name.
	'localClientDatabases' => [],

	// Formats that shall be available via Special:EntityData.
	// The first format will be used as the default.
	// Even if a format is allowed here, it may not be supported
	// because when missing optional dependencies (e.g. purtle).
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
		'jsonld',

		// hardcoded internal handling
		'html',
	],

	'entityDataCachePaths' => function() {
		return [
			// JSON from entity page JS, compare wikibase.entityPage.entityLoaded.js
			wfAppendQuery(
				str_replace( '$1', 'Special:EntityData/{entity_id}.json', $GLOBALS['wgArticlePath'] ),
				'revision={revision_id}'
			),
			// Turtle from Query Service updater, compare WikibaseRepository.java
			wfAppendQuery(
				str_replace( '$1', 'Special:EntityData/{entity_id}.ttl', $GLOBALS['wgArticlePath'] ),
				'flavor=dump&revision={revision_id}'
			),
		];
	},

	'enableEntitySearchUI' => true,

	'dataRightsUrl' => function() {
		return $GLOBALS['wgRightsUrl'] ?? '';
	},

	'rdfDataRightsUrl' => 'http://creativecommons.org/publicdomain/zero/1.0/',

	'dataRightsText' => function() {
		return $GLOBALS['wgRightsText'] ?? '';
	},

	'sparqlEndpoint' => null,

	'transformLegacyFormatOnExport' => true,

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
	'sharedCacheKeyPrefix' => 'wikibase_shared/' . $GLOBALS['wgDBname'],
	'sharedCacheKeyGroup' => $GLOBALS['wgDBname'],

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
			'es-formal'   => 'es-x-formal',
			'hu-formal'   => 'hu-x-formal',
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
		// (same order as in GeoData includes/Globe.php)
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
		'http://www.wikidata.org/entity/Q339' => 'pluto',

		// additional globes not recognized by GeoData as of 2022-09-30;
		// not a problem, it mainly means distance calculation is unavailable
		// (sorted by numerical item ID)
		'http://www.wikidata.org/entity/Q193' => 'saturn',
		'http://www.wikidata.org/entity/Q319' => 'jupiter',
		'http://www.wikidata.org/entity/Q324' => 'uranus',
		'http://www.wikidata.org/entity/Q596' => 'ceres',
		'http://www.wikidata.org/entity/Q3030' => 'vesta',
		'http://www.wikidata.org/entity/Q3257' => 'amalthea',
		'http://www.wikidata.org/entity/Q6604' => 'charon',
		'http://www.wikidata.org/entity/Q11558' => 'bennu',
		'http://www.wikidata.org/entity/Q15662' => 'puck',
		'http://www.wikidata.org/entity/Q16081' => 'proteus',
		'http://www.wikidata.org/entity/Q16765' => 'thebe',
		'http://www.wikidata.org/entity/Q16711' => 'eros',
		'http://www.wikidata.org/entity/Q17751' => 'epimetheus',
		'http://www.wikidata.org/entity/Q17754' => 'janus',
		'http://www.wikidata.org/entity/Q107556' => 'lutetia',
		'http://www.wikidata.org/entity/Q149012' => 'ida',
		'http://www.wikidata.org/entity/Q149374' => 'itokawa',
		'http://www.wikidata.org/entity/Q149417' => 'mathilde',
		'http://www.wikidata.org/entity/Q150249' => 'steins',
		'http://www.wikidata.org/entity/Q158244' => 'gaspra',
		'http://www.wikidata.org/entity/Q510728' => 'dactyl',
		'http://www.wikidata.org/entity/Q844672' => 'churyumov',
		'http://www.wikidata.org/entity/Q1385178' => 'ryugu',
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

	// List of properties to be indexed
	'searchIndexProperties' => [],
	// List of property types to be indexed
	'searchIndexTypes' => [],
	// List of properties to be excluded from indexing
	'searchIndexPropertiesExclude' => [],
	// List of properties that, if in a qualifier, will be used for indexing quantities
	'searchIndexQualifierPropertiesForQuantity' => [],

	// Search profiles available in wbsearchentities & query+wbsearch
	'searchProfiles' => [ 'default' => null ],

	// DB group to use in dump maintenance scripts. Defaults to "dump", per T147169.
	'dumpDBDefaultGroup' => 'dump',

	'useKartographerGlobeCoordinateFormatter' => false,

	'useKartographerMaplinkInWikitext' => false,

	// Temporary, see: T199197
	'enableRefTabs' => false,

	/**
	 * The default for this idGenerator will have to remain using the 'original'
	 * generator as the 'upsert' generator only supports MySQL currently.
	 *
	 * @var string 'original' or 'mysql-upsert' depending on what implementation of IdGenerator
	 * you wish to use, or 'auto' to pick one depending on the database type.
	 */
	'idGenerator' => 'original',

	/**
	 * Whether use a separate master database connection to generate new id or not.
	 *
	 * @var bool
	 * @see https://phabricator.wikimedia.org/T213817
	 */
	'idGeneratorSeparateDbConnection' => false,

	/**
	 * Number to increase for ping limiter in case creating an entity errors in API
	 *
	 * @var int
	 * @see https://phabricator.wikimedia.org/T284538
	 */
	'idGeneratorInErrorPingLimiter' => 0,

	'entityTypesWithoutRdfOutput' => [],

	'defaultEntityNamespaces' => false,

	'entitySources' => function ( SettingsArray $settings ) {
		if ( $settings->getSetting( 'defaultEntityNamespaces' ) ) {
			global $wgServer;

			if ( !defined( 'WB_NS_ITEM' ) ) {
				throw new Exception( 'Constant WB_NS_ITEM is not defined' );
			}

			if ( !defined( 'WB_NS_PROPERTY' ) ) {
				throw new Exception( 'Constant WB_NS_PROPERTY is not defined' );
			}

			$entityNamespaces = [
				'item' => WB_NS_ITEM,
				'property' => WB_NS_PROPERTY,
			];

			$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
			$hookContainer->run( 'WikibaseRepoEntityNamespaces', [ &$entityNamespaces ] );

			return [
				$settings->getSetting( 'localEntitySourceName' ) => [
					'entityNamespaces' => $entityNamespaces,
					'repoDatabase' => false,
					'baseUri' => $wgServer . '/entity/',
					'rdfNodeNamespacePrefix' => 'wd',
					'rdfPredicateNamespacePrefix' => '',
					'interwikiPrefix' => '',
				],
			];
		}

		throw new Exception( 'entitySources must be configured manually (or use the example settings)' );
	},

	'localEntitySourceName' => 'local',

	// Do not enable this one in production environments, unless you know what you are doing when
	// using the script there.
	'enablePopulateWithRandomEntitiesAndTermsScript' => false,

	// Namespace id for entity schema data type
	'entitySchemaNamespace' => 640,

	'dataBridgeEnabled' => false,

	'changeVisibilityNotificationClientRCMaxAge' => $GLOBALS['wgRCMaxAge'],
	'changeVisibilityNotificationJobBatchSize' => 3,

	'deleteNotificationClientRCMaxAge' => $GLOBALS['wgRCMaxAge'],

	'termFallbackCacheVersion' => null,

	'wikibasePingback' => false,
	'pingbackHost' => 'https://www.mediawiki.org/beacon/event',

	/**
	 * @note This config options is primarily added for Wikidata transition use-case and can be
	 * considered temporary. It could be removed in the future with no warning.
	 *
	 * @var bool Whether to serialize empty containers as {} instead of []
	 * in the json output of wbgetentities for lexemes
	 */
	'tmpSerializeEmptyListsAsObjects' => false,

	/**
	 * @note The entities set in this configuration are subject to data
	 * modification and may be relabeled, removed, or merged with each other.
	 *
	 * @var string[]
	 * @see https://phabricator.wikimedia.org/T219215
	 */
	'sandboxEntityIds' => [
		'mainItem' => 'Q999999998',
		'auxItem' => 'Q999999999',
	],

	/**
	 * Tags for edits made via UpdateRepo jobs.
	 *
	 * @var string[]
	 * @see https://phabricator.wikimedia.org/T286772
	 */
	'updateRepoTags' => [],

	/**
	 * Tags for edits made via WikibaseView frontend code.
	 *
	 * @var string[]
	 * @see https://phabricator.wikimedia.org/T286773
	 */
	'viewUiTags' => [],

	/**
	 * Tags for edits made via the termbox.
	 *
	 * @var string[]
	 * @see https://phabricator.wikimedia.org/T286775
	 */
	'termboxTags' => [],

	/**
	 * Tags for edits made via special pages.
	 *
	 * @var string[]
	 * @see https://phabricator.wikimedia.org/T286774
	 */
	'specialPageTags' => [],

	/**
	 * @note This config options is primarily added for Wikidata transition use-case and can be
	 * considered temporary. It could be removed in the future with no warning.
	 *
	 * @var bool Whether to normalize data values when saving edits
	 * @see https://phabricator.wikimedia.org/T251480
	 */
	'tmpNormalizeDataValues' => false,

	/**
	 * @note This config options is primarily added for Wikidata transition use-case and can be
	 * considered temporary. It could be removed in the future with no warning.
	 *
	 * @var bool Whether to enable the 'mul' language code,
	 * adding it to the term language codes and falling back to it before the implicit 'en' fallback
	 * @see https://phabricator.wikimedia.org/T297393
	 */
	'tmpEnableMulLanguageCode' => false,
];
