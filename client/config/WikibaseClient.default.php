<?php

use MediaWiki\MediaWikiServices;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\WikibaseRepo;

// manually load EntityUsage so we can use its constants below –
// extension.json AutoloadNamespaces has not been processed yet
require_once __DIR__ . '/../includes/Usage/EntityUsage.php';

/**
 * This file assigns the default values to all Wikibase Client settings.
 *
 * This file is NOT an entry point the Wikibase Client extension. Use this instead:
 * wfLoadExtension( 'WikibaseClient', "$IP/extensions/Wikibase/extension-client.json" );
 * It should furthermore not be included from outside the extension.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */

return call_user_func( function() {
	global $wgLanguageCode;

	$defaults = [
		'namespaces' => [], // by default, include all namespaces; deprecated as of 0.4
		'excludeNamespaces' => [],
		// @todo would be great to just get this from the sites stuff
		// but we will need to make sure the caching works good enough
		'siteLocalID' => $wgLanguageCode,
		'languageLinkSiteGroup' => null,
		'languageLinkAllowedSiteGroups' => null,
		'injectRecentChanges' => true,
		'showExternalRecentChanges' => true,
		'sendEchoNotification' => false,
		'echoIcon' => false,
		'allowDataTransclusion' => true,
		'referencedEntityIdAccessLimit' => 3,
		'referencedEntityIdMaxDepth' => 4,
		'referencedEntityIdMaxReferencedEntityVisits' => 50,
		'pageSchemaNamespaces' => [],
		'allowLocalShortDesc' => false,
		'forceLocalShortDesc' => false,
		'propagateChangesToRepo' => true,
		'propertyOrderUrl' => null,
		// List of additional CSS class names for site links that have badges,
		// e.g. [ 'Q101' => 'badge-goodarticle' ]
		'badgeClassNames' => [],
		// Allow accessing data from other items in the parser functions and via Lua
		'allowArbitraryDataAccess' => true,
		// Maximum number of full entities that can be accessed on a page. This does
		// not include convenience functions like mw.wikibase.label that use TermLookup
		// instead of loading a full entity.
		'entityAccessLimit' => 250,
		// Allow accessing data in the user's language rather than the content language
		// in the parser functions and via Lua.
		// Allows users to split the ParserCache by user language.
		'allowDataAccessInUserLanguage' => false,

		/**
		 * Prefix to use for cache keys that should be shared among a Wikibase Repo instance and all
		 * its clients. This is for things like caching entity blobs in memcached.
		 *
		 * The default here assumes Wikibase Repo + Client installed together on the same wiki. For
		 * a multiwiki / wikifarm setup, to configure shared caches between clients and repo, this
		 * needs to be set to the same value in both client and repo wiki settings.
		 *
		 * For Wikidata production, we set it to 'wikibase-shared/wikidata_1_25wmf24-wikidatawiki',
		 * which is 'wikibase_shared/' + deployment branch name + '-' + repo database name, and have
		 * it set in both $wgWBClientSettings and $wgWBRepoSettings.
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

		/**
		 * List of data types (by data type id) not enabled on the wiki.
		 * This setting is intended to aid with deployment of new data types
		 * or on new Wikibase installs without items and properties yet.
		 *
		 * This setting should be consistent with the corresponding setting on the repo.
		 *
		 * WARNING: Disabling a data type after it is in use is dangerous
		 * and might break items.
		 */
		'disabledDataTypes' => [],

		'disabledUsageAspects' => [],

		// The type of object cache to use. Use CACHE_XXX constants.
		// This is both a repo and client setting, and should be set to the same value in
		// repo and clients for multiwiki setups.
		'sharedCacheType' => $GLOBALS['wgMainCacheType'],

		// Batch size for UpdateHtmlCacheJob
		'purgeCacheBatchSize' => function ( SettingsArray $settings ) {
			$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
			return $settings->hasSetting( 'wikiPageUpdaterDbBatchSize' )
				? $settings->getSetting( 'wikiPageUpdaterDbBatchSize' )
				: $mainConfig->get( 'UpdateRowsPerJob' );
		},

		// Batch size for InjectRCRecordsJob
		'recentChangesBatchSize' => function ( SettingsArray $settings ) {
			$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
			return $settings->hasSetting( 'wikiPageUpdaterDbBatchSize' )
				? $settings->getSetting( 'wikiPageUpdaterDbBatchSize' )
				: $mainConfig->get( 'UpdateRowsPerJob' );
		},

		'useKartographerGlobeCoordinateFormatter' => false,
		'useKartographerMaplinkInWikitext' => false,
		'trackLuaFunctionCallsPerSiteGroup' => false,
		'trackLuaFunctionCallsPerWiki' => false,
		'trackLuaFunctionCallsSampleRate' => 1,

		'dataBridgeEnabled' => false, # if true, the next setting must also be specified
		# 'dataBridgeHrefRegExp' => '^http://localhost/index\.php/(Item:(Q[1-9][0-9]*)).*#(P[1-9][0-9]*)$',
		'dataBridgeIssueReportingLink' =>
		'https://phabricator.wikimedia.org/maniphest/task/edit/form/1/?title=Wikidata+Bridge+error&description=<body>&tags=Wikidata-Bridge',

		// IDs of some well-known properties used to format references
		'wellKnownReferencePropertyIds' => [
			// (note: keys are not arbitrary, the software knows exactly these and no others)
			'referenceUrl' => null,
			'title' => null,
			'statedIn' => null,
			'author' => null,
			'publisher' => null,
			'publicationDate' => null,
			'retrievedDate' => null,
		],

		'termFallbackCacheVersion' => null,

		// enable implicit usage on the description of a page in its content language (T191831)
		'enableImplicitDescriptionUsage' => false,

		// tags for edits made via the linkitem feature
		'linkItemTags' => [],

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

	// Some defaults depend on information not available at this time.
	// Especially, if the repository may be active on the local wiki, and
	// we need to adjust some defaults accordingly.
	// We use Closures to calculate such settings on the fly, the first time they
	// are used. See SettingsArray::setSetting() for details.

	//NOTE: when this is executed, the repo extension may not have been initialized yet.
	//      We need to defer the check and do it inside the closures.
	//      We use the pseudo-setting thisWikiIsTheRepo to store this information.
	//      thisWikiIsTheRepo should really never be overwritten, except for testing.

	$defaults['thisWikiIsTheRepo'] = function ( SettingsArray $settings ) {
		// determine whether the repo extension is present
		return WikibaseSettings::isRepoEnabled();
	};

	$defaults['entitySources'] = function ( SettingsArray $settings ) {
		if ( $settings->getSetting( 'thisWikiIsTheRepo' ) ) {
			// copy the repo’s effective entitySources setting
			$entitySourceDefinitions = WikibaseRepo::getEntitySourceDefinitions();
			$entitySources = [];

			foreach ( $entitySourceDefinitions->getSources() as $source ) {
				if ( $source->getType() === ApiEntitySource::TYPE ) {
					// WikibaseClient.EntitySourceAndTypeDefinitions service wiring can’t parse other types yet
					continue;
				}

				$entityNamespaces = [];
				foreach ( $source->getEntityTypes() as $entityType ) {
					$entityNamespaces[$entityType] = $source->getEntityNamespaceIds()[$entityType]
						. '/' . $source->getEntitySlotNames()[$entityType];
				}

				$entitySources[$source->getSourceName()] = [
					'repoDatabase' => $source->getDatabaseName(),
					'entityNamespaces' => $entityNamespaces,
					'baseUri' => $source->getConceptBaseUri(),
					'rdfNodeNamespacePrefix' => $source->getRdfNodeNamespacePrefix(),
					'rdfPredicateNamespacePrefix' => $source->getRdfPredicateNamespacePrefix(),
					'interwikiPrefix' => $source->getInterwikiPrefix(),
				];
			}

			return $entitySources;
		}

		throw new Exception( 'entitySources must be configured for non-repo client wikis' );
	};

	$defaults['itemAndPropertySourceName'] = function ( SettingsArray $settings ) {
		if ( $settings->getSetting( 'thisWikiIsTheRepo' ) ) {
			return WikibaseRepo::getSettings()->getSetting( 'localEntitySourceName' );
		}

		return 'local';
	};

	$defaults['repoSiteName'] = function ( SettingsArray $settings ) {
		// This uses $wgSitename if this wiki is the repo.  Otherwise, set this to
		// either an i18n message key and the message will be used, if it exists.
		// If repo site name does not need translation, then set this as a string.
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? $GLOBALS['wgSitename'] : 'Wikibase';
	};

	$defaults['repoUrl'] = function ( SettingsArray $settings ) {
		// use $wgServer if this wiki is the repo, otherwise default to wikidata.org
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? $GLOBALS['wgServer'] : '//www.wikidata.org';
	};

	$defaults['repoArticlePath'] = function ( SettingsArray $settings ) {
		// use $wgArticlePath if this wiki is the repo, otherwise default to /wiki/$1
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? $GLOBALS['wgArticlePath'] : '/wiki/$1';
	};

	$defaults['repoScriptPath'] = function ( SettingsArray $settings ) {
		// use $wgScriptPath if this wiki is the repo, otherwise default to /w
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? $GLOBALS['wgScriptPath'] : '/w';
	};

	$defaults['siteGlobalID'] = function ( SettingsArray $settings ) {
		// The database name is a sane default for the site ID.
		// On Wikimedia sites, this is always correct.
		return $GLOBALS['wgDBname'];
	};

	$defaults['repoSiteId'] = function( SettingsArray $settings ) {
		$entitySources = $settings->getSetting( 'entitySources' );
		$itemAndPropertySourceName = $settings->getSetting( 'itemAndPropertySourceName' );
		$repoDatabase = $entitySources[$itemAndPropertySourceName]['repoDatabase'];

		return ( $repoDatabase === false )
			? $settings->getSetting( 'siteGlobalID' )
			: $repoDatabase;
	};

	$defaults['siteGroup'] = function ( SettingsArray $settings ) {
		// by default lookup from SiteLookup, can override with setting for performance reasons
		return null;
	};

	$defaults['otherProjectsLinks'] = function ( SettingsArray $settings ) {
		$otherProjectsSitesProvider = WikibaseClient::getOtherProjectsSitesProvider();
		return $otherProjectsSitesProvider->getOtherProjectsSiteIds( $settings->getSetting( 'siteLinkGroups' ) );
	};

	// Base URL of geo shape storage frontend. Used primarily to build links to the geo shapes. Will
	// be concatenated with the page title, so should end with "/" or "title=". Special characters
	// (e.g. space, percent, etc.) should NOT be encoded.
	$defaults['geoShapeStorageBaseUrl'] = 'https://commons.wikimedia.org/wiki/';

	// Base URL of tabular data storage frontend. Used primarily to build links to the tabular data
	// pages. Will be concatenated with the page title, so should end with "/" or "title=". Special
	// characters (e.g. space, percent, etc.) should NOT be encoded.
	$defaults['tabularDataStorageBaseUrl'] = 'https://commons.wikimedia.org/wiki/';

	// Namespace id for entity schema data type
	$defaults['entitySchemaNamespace'] = 640;

	// Disabled entity access
	$defaults['disabledAccessEntityTypes'] = [];

	// The limit to issue a warning when number of entities used in a page hit that
	$defaults['entityUsagePerPageLimit'] = 100;

	// The limit to turn the usage into a general one when there is too many modifiers
	$defaults['entityUsageModifierLimits'] = [
		EntityUsage::DESCRIPTION_USAGE => 30,
		EntityUsage::LABEL_USAGE => 30,
		EntityUsage::STATEMENT_USAGE => 10,
	];

	// Batch size for adding entity usage records
	$defaults['addEntityUsagesBatchSize'] = 500;

	return $defaults;
} );
