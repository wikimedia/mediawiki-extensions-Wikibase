<?php

namespace Wikibase\Client;

/**
 * GOAT
 */
class Registrar {

	public function registerExtension() {
		global $wgAPIListModules,
			$wgAPIMetaModules,
		   	$wgAPIPropModules,
		   	$wgAPIUselessQueryPages,
		   	$wgHooks,
		   	$wgJobClasses,
		   	$wgResourceModules,
		   	$wgSpecialPages,
		   	$wgWBClientDataTypes,
		   	$wgWBClientSettings,
			$wgWikibaseMultiRepositoryServiceWiringFiles,
		   	$wgWikibasePerRepositoryServiceWiringFiles;

		if ( defined( 'WBC_VERSION' ) ) {
			// Do not initialize more than once.
			return;
		}

		define( 'WBC_VERSION', '0.5 alpha' );

		// TODO: that needed?
		define( 'WBC_DIR', __DIR__ );

		// Include the WikibaseLib extension if that hasn't been done yet, since it's required for WikibaseClient to work.
		if ( !defined( 'WBL_VERSION' ) ) {
			include_once __DIR__ . '/../lib/WikibaseLib.php';
		}

		if ( !defined( 'WBL_VERSION' ) ) {
			throw new \Exception( 'WikibaseClient depends on the WikibaseLib extension.' );
		}

		if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
			include_once __DIR__ . '/../view/WikibaseView.php';
		}

		if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
			throw new \Exception( 'WikibaseClient depends on WikibaseView.' );
		}

		// Registry and definition of data types
		$wgWBClientDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
		$clientDatatypes = require __DIR__ . '/WikibaseClient.datatypes.php';

		// merge WikibaseClient.datatypes.php into $wgWBClientDataTypes
		foreach ( $clientDatatypes as $type => $clientDef ) {
			$baseDef = isset( $wgWBClientDataTypes[$type] ) ? $wgWBClientDataTypes[$type] : [];
			$wgWBClientDataTypes[$type] = array_merge( $baseDef, $clientDef );
		}

		$wgWikibaseMultiRepositoryServiceWiringFiles = [ __DIR__ . '/../data-access/src/MultiRepositoryServiceWiring.php' ];
		$wgWikibasePerRepositoryServiceWiringFiles = [ __DIR__ . '/../data-access/src/PerRepositoryServiceWiring.php' ];

		// Those two Hook defs are only here because willing to keep the comment for now. Should go to extension.json

		// for client notifications (requires the Echo extension)
		// note that Echo calls BeforeCreateEchoEvent hook when it is being initialized,
		// thus we have to register these two handlers disregarding Echo is loaded or not
		$wgHooks['BeforeCreateEchoEvent'][] = '\Wikibase\Client\Hooks\EchoSetupHookHandlers::onBeforeCreateEchoEvent';
		$wgHooks['EchoGetBundleRules'][] = '\Wikibase\Client\Hooks\EchoNotificationsHandlers::onEchoGetBundleRules';

		// tracking local edits
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			// NOTE: Usage tracking is pointless during unit testing, and slows things down.
			// Also, usage tracking can trigger failures when it tries to access the repo database
			// when WikibaseClient is tested without WikibaseRepo enabled.
			// NOTE: UsageTrackingIntegrationTest explicitly enables these hooks and asserts that
			// they are functioning correctly. If any hooks used for tracking are added or changed,
			// that must be reflected in UsageTrackingIntegrationTest.
			$wgHooks['LinksUpdateComplete'][] = '\Wikibase\Client\Hooks\DataUpdateHookHandlers::onLinksUpdateComplete';
			$wgHooks['ArticleDeleteComplete'][] = '\Wikibase\Client\Hooks\DataUpdateHookHandlers::onArticleDeleteComplete';
			$wgHooks['ParserCacheSaveComplete'][] = '\Wikibase\Client\Hooks\DataUpdateHookHandlers::onParserCacheSaveComplete';
			$wgHooks['TitleMoveComplete'][] = '\Wikibase\Client\Hooks\UpdateRepoHookHandlers::onTitleMoveComplete';
			$wgHooks['ArticleDeleteComplete'][] = '\Wikibase\Client\Hooks\UpdateRepoHookHandlers::onArticleDeleteComplete';
		}

		$wgJobClasses['wikibase-InjectRCRecords'] = function ( Title $unused, array $params ) {
			$mwServices = MediaWiki\MediaWikiServices::getInstance();
			$wbServices = WikibaseClient::getDefaultInstance();

			$job = new Changes\InjectRCRecordsJob(
				$mwServices->getDBLoadBalancerFactory(),
				$wbServices->getStore()->getEntityChangeLookup(),
				$wbServices->getEntityChangeFactory(),
				$wbServices->getRecentChangeFactory(),
				$params
			);

			$job->setRecentChangesDuplicateDetector( $wbServices->getStore()->getRecentChangesDuplicateDetector() );

			$job->setLogger( MediaWiki\Logger\LoggerFactory::getInstance( 'wikibase.client.pageupdates' ) );
			$job->setStats( $mwServices->getStatsdDataFactory() );

			return $job;
		};

		// api modules
		$wgAPIMetaModules['wikibase'] = [
			'class' => Api\ApiClientInfo::class,
			'factory' => function( ApiQuery $apiQuery, $moduleName ) {
				return new Api\ApiClientInfo(
					WikibaseClient::getDefaultInstance()->getSettings(),
					$apiQuery,
					$moduleName
				);
			}
		];

		$wgAPIPropModules['pageterms'] = [
			'class' => Api\PageTerms::class,
			'factory' => function ( ApiQuery $apiQuery, $moduleName ) {
				// FIXME: HACK: make pageterms work directly on entity pages on the repo.
				// We should instead use an EntityIdLookup that combines the repo and the client
				// implementation, see T115117.
				// NOTE: when changing repo and/or client integration, remember to update the
				// self-documentation of the API module in the "apihelp-query+pageterms-description"
				// message and the PageTerms::getExamplesMessages() method.
				if ( defined( 'WB_VERSION' ) ) {
					$repo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
					$termIndex = $repo->getStore()->getTermIndex();
					$entityIdLookup = $repo->getEntityContentFactory();
				} else {
					$client = WikibaseClient::getDefaultInstance();
					$termIndex = $client->getStore()->getTermIndex();
					$entityIdLookup = $client->getStore()->getEntityIdLookup();
				}

				return new Api\PageTerms(
					$termIndex,
					$entityIdLookup,
					$apiQuery,
					$moduleName
				);
			}
		];

		$wgAPIPropModules['wbentityusage'] = [
			'class' => Api\ApiPropsEntityUsage::class,
			'factory' => function ( ApiQuery $query, $moduleName ) {
				$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();
				return new Api\ApiPropsEntityUsage(
					$query,
					$moduleName,
					$repoLinker
				);
			}
		];
		$wgAPIListModules['wblistentityusage'] = [
			'class' => Api\ApiListEntityUsage::class,
			'factory' => function ( ApiQuery $apiQuery, $moduleName ) {
				return new Api\ApiListEntityUsage(
					$apiQuery,
					$moduleName,
					WikibaseClient::getDefaultInstance()->newRepoLinker()
				);
			}
		];

		$wgAPIUselessQueryPages[] = 'PagesWithBadges';

		$wgSpecialPages['PagesWithBadges'] = function() {

			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$settings = $wikibaseClient->getSettings();
			return new Specials\SpecialPagesWithBadges(
				new Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory(
					$wikibaseClient->getLanguageFallbackChainFactory(),
					$wikibaseClient->getTermLookup(),
					$wikibaseClient->getTermBuffer()
				),
				array_keys( $settings->getSetting( 'badgeClassNames' ) ),
				$settings->getSetting( 'siteGlobalID' )
			);
		};
		$wgSpecialPages['EntityUsage'] = function () {
			return new Specials\SpecialEntityUsage(
				WikibaseClient::getDefaultInstance()->getEntityIdParser()
			);
		};

		// Resource loader modules
		$wgResourceModules = array_merge(
			$wgResourceModules,
			include __DIR__ . '/resources/Resources.php'
		);

		$wgWBClientSettings = array_merge(
			require __DIR__ . '/../lib/config/WikibaseLib.default.php',
			require __DIR__ . '/config/WikibaseClient.default.php',
			$wgWBClientSettings
		);
	}

}
