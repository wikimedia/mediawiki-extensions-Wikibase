<?php

/**
 * Welcome to the inside of Wikibase,              <>
 * the software that powers                   /\        /\
 * Wikidata and other                       <{  }>    <{  }>
 * structured data websites.        <>   /\   \/   /\   \/   /\   <>
 *                                     //  \\    //  \\    //  \\
 * It is Free Software.              <{{    }}><{{    }}><{{    }}>
 *                                /\   \\  //    \\  //    \\  //   /\
 *                              <{  }>   ><        \/        ><   <{  }>
 *                                \/   //  \\              //  \\   \/
 *                            <>     <{{    }}>     +--------------------------+
 *                                /\   \\  //       |                          |
 *                              <{  }>   ><        /|  W  I  K  I  B  A  S  E  |
 *                                \/   //  \\    // |                          |
 * We are                            <{{    }}><{{  +--------------------------+
 * looking for people                  \\  //    \\  //    \\  //
 * like you to join us in           <>   \/   /\   \/   /\   \/   <>
 * developing it further. Find              <{  }>    <{  }>
 * out more at http://wikiba.se               \/        \/
 * and join the open data revolution.              <>
 */

/**
 * Entry point for the Wikibase Client extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Client
 *
 * @license GPL-2.0-or-later
 */

// @codingStandardsIgnoreFile

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not an entry point.\n" );
}

if ( defined( 'WBC_VERSION' ) ) {
	// Do not initialize more than once.
	return;
}

define( 'WBC_VERSION', '0.5 alpha' );

// Needs to be 1.26c because version_compare() works in confusing ways.
if ( version_compare( $GLOBALS['wgVersion'], '1.26c', '<' ) ) {
	die( "<b>Error:</b> Wikibase requires MediaWiki 1.26 or above.\n" );
}

define( 'WBC_DIR', __DIR__ );

// Include the WikibaseLib extension if that hasn't been done yet, since it's required for WikibaseClient to work.
if ( !defined( 'WBL_VERSION' ) ) {
	include_once __DIR__ . '/../lib/WikibaseLib.php';
}

if ( !defined( 'WBL_VERSION' ) ) {
	throw new Exception( 'WikibaseClient depends on the WikibaseLib extension.' );
}

if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
	include_once __DIR__ . '/../view/WikibaseView.php';
}

if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
	throw new Exception( 'WikibaseClient depends on WikibaseView.' );
}

// Load autoload info as long as extension classes are not PSR-4-autoloaded
require_once __DIR__  . '/autoload.php';

call_user_func( function() {
	global $wgAPIListModules,
		$wgAPIMetaModules,
		$wgAPIPropModules,
		$wgAPIUselessQueryPages,
		$wgExtensionCredits,
		$wgExtensionFunctions,
		$wgExtensionMessagesFiles,
		$wgHooks,
		$wgJobClasses,
		$wgMessagesDirs,
		$wgRecentChangesFlags,
		$wgResourceModules,
		$wgSpecialPages,
		$wgTrackingCategories,
		$wgWBClientDataTypes,
		$wgWikibaseMultiRepositoryServiceWiringFiles,
		$wgWikibasePerRepositoryServiceWiringFiles,
		$wgWBClientSettings;

	$wgExtensionCredits['wikibase'][] = array(
		'path' => __DIR__ . '/../README.md',
		'name' => 'WikibaseClient',
		'author' => array(
			'The Wikidata team',
		),
		'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase_Client',
		'descriptionmsg' => 'wikibase-client-desc',
		'license-name' => 'GPL-2.0-or-later'
	);

	// Registry and definition of data types
	$wgWBClientDataTypes = require ( __DIR__ . '/../lib/WikibaseLib.datatypes.php' );
	$clientDatatypes = require ( __DIR__ . '/WikibaseClient.datatypes.php' );

	// merge WikibaseClient.datatypes.php into $wgWBClientDataTypes
	foreach ( $clientDatatypes as $type => $clientDef ) {
		$baseDef = isset( $wgWBClientDataTypes[$type] ) ? $wgWBClientDataTypes[$type] : array();
		$wgWBClientDataTypes[$type] = array_merge( $baseDef, $clientDef );
	}

	$wgWikibaseMultiRepositoryServiceWiringFiles = [ __DIR__ . '/../data-access/src/MultiRepositoryServiceWiring.php' ];
	$wgWikibasePerRepositoryServiceWiringFiles = [ __DIR__ . '/../data-access/src/PerRepositoryServiceWiring.php' ];

	// i18n
	$wgMessagesDirs['wikibaseclient'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['Wikibaseclientalias'] = __DIR__ . '/WikibaseClient.i18n.alias.php';
	$wgExtensionMessagesFiles['wikibaseclientmagic'] = __DIR__ . '/WikibaseClient.i18n.magic.php';

	// Tracking categories
	$wgTrackingCategories[] = 'unresolved-property-category';
	$wgTrackingCategories[] = 'connected-redirect-category';

	// Hooks
	$wgHooks['UnitTestsList'][] = '\Wikibase\ClientHooks::registerUnitTests';
	$wgHooks['BaseTemplateToolbox'][] = '\Wikibase\ClientHooks::onBaseTemplateToolbox';
	$wgHooks['OldChangesListRecentChangesLine'][] =
		'\Wikibase\Client\Hooks\ChangesListLinesHandler::onOldChangesListRecentChangesLine';
	$wgHooks['EnhancedChangesListModifyLineData'][] =
		'\Wikibase\Client\Hooks\ChangesListLinesHandler::onEnhancedChangesListModifyLineData';
	$wgHooks['EnhancedChangesListModifyBlockLineData'][] =
		'\Wikibase\Client\Hooks\ChangesListLinesHandler::onEnhancedChangesListModifyBlockLineData';
	$wgHooks['OutputPageParserOutput'][] = '\Wikibase\Client\Hooks\SidebarHookHandlers::onOutputPageParserOutput';
	$wgHooks['SkinTemplateGetLanguageLink'][] = '\Wikibase\Client\Hooks\SidebarHookHandlers::onSkinTemplateGetLanguageLink';
	$wgHooks['ContentAlterParserOutput'][] = '\Wikibase\Client\Hooks\ParserOutputUpdateHookHandlers::onContentAlterParserOutput';
	$wgHooks['SidebarBeforeOutput'][] = '\Wikibase\Client\Hooks\SidebarHookHandlers::onSidebarBeforeOutput';

	$wgHooks['ParserFirstCallInit'][] = '\Wikibase\ClientHooks::onParserFirstCallInit';
	$wgHooks['SkinTemplateOutputPageBeforeExec'][] =
		'\Wikibase\Client\Hooks\SkinTemplateOutputPageBeforeExecHandler::onSkinTemplateOutputPageBeforeExec';
	$wgHooks['SpecialMovepageAfterMove'][] = '\Wikibase\Client\Hooks\MovePageNotice::onSpecialMovepageAfterMove';
	$wgHooks['GetPreferences'][] = '\Wikibase\ClientHooks::onGetPreferences';
	$wgHooks['BeforePageDisplay'][] = '\Wikibase\ClientHooks::onBeforePageDisplay';
	$wgHooks['BeforePageDisplay'][] = '\Wikibase\ClientHooks::onBeforePageDisplayAddJsConfig';
	$wgHooks['ScribuntoExternalLibraries'][] = '\Wikibase\ClientHooks::onScribuntoExternalLibraries';
	$wgHooks['InfoAction'][] = '\Wikibase\Client\Hooks\InfoActionHookHandler::onInfoAction';
	$wgHooks['EditPage::showStandardInputs:options'][] = '\Wikibase\ClientHooks::onEditAction';
	$wgHooks['BaseTemplateAfterPortlet'][] = '\Wikibase\ClientHooks::onBaseTemplateAfterPortlet';
	$wgHooks['ArticleDeleteAfterSuccess'][] = '\Wikibase\ClientHooks::onArticleDeleteAfterSuccess';
	$wgHooks['ParserLimitReportPrepare'][] = '\Wikibase\Client\Hooks\ParserLimitReportPrepareHookHandler::onParserLimitReportPrepare';
	$wgHooks['FormatAutocomments'][] = '\Wikibase\ClientHooks::onFormat';
	$wgHooks['ParserClearState'][] = '\Wikibase\Client\Hooks\ParserClearStateHookHandler::onParserClearState';
	$wgHooks['AbortEmailNotification'][] = '\Wikibase\ClientHooks::onAbortEmailNotification';

	// for client notifications (requires the Echo extension)
	// note that Echo calls BeforeCreateEchoEvent hook when it is being initialized,
	// thus we have to register these two handlers disregarding Echo is loaded or not
	$wgHooks['BeforeCreateEchoEvent'][] = '\Wikibase\Client\Hooks\EchoSetupHookHandlers::onBeforeCreateEchoEvent';
	$wgHooks['EchoGetBundleRules'][] = '\Wikibase\Client\Hooks\EchoNotificationsHandlers::onEchoGetBundleRules';

	// conditionally register the remaining two handlers which would otherwise fail
	$wgExtensionFunctions[] = '\Wikibase\ClientHooks::onExtensionLoad';

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

	// recent changes / watchlist hooks
	$wgHooks['ChangesListSpecialPageQuery'][] = '\Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers::onChangesListSpecialPageQuery';

	// magic words
	$wgHooks['MagicWordwgVariableIDs'][] = '\Wikibase\Client\Hooks\MagicWordHookHandlers::onMagicWordwgVariableIDs';
	$wgHooks['ParserGetVariableValueSwitch'][] = '\Wikibase\Client\Hooks\MagicWordHookHandlers::onParserGetVariableValueSwitch';
	$wgHooks['ResourceLoaderJqueryMsgModuleMagicWords'][] = '\Wikibase\Client\Hooks\MagicWordHookHandlers::onResourceLoaderJqueryMsgModuleMagicWords';

	// update hooks
	$wgHooks['LoadExtensionSchemaUpdates'][] = '\Wikibase\Client\Usage\Sql\SqlUsageTrackerSchemaUpdater::onSchemaUpdate';

	// job classes
	$wgJobClasses['wikibase-addUsagesForPage'] = Wikibase\Client\Store\AddUsagesForPageJob::class;
	$wgJobClasses['ChangeNotification'] = Wikibase\Client\ChangeNotificationJob::class;
	$wgJobClasses['wikibase-InjectRCRecords'] = function ( Title $unused, array $params ) {
		$mwServices = MediaWiki\MediaWikiServices::getInstance();
		$wbServices = Wikibase\Client\WikibaseClient::getDefaultInstance();

		$job = new Wikibase\Client\Changes\InjectRCRecordsJob(
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
	$wgAPIMetaModules['wikibase'] = array(
		'class' => Wikibase\Client\Api\ApiClientInfo::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			return new Wikibase\Client\Api\ApiClientInfo(
				Wikibase\Client\WikibaseClient::getDefaultInstance()->getSettings(),
				$apiQuery,
				$moduleName
			);
		}
	);

	$wgAPIPropModules['pageterms'] = array(
		'class' => Wikibase\Client\Api\PageTerms::class,
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
				$client = Wikibase\Client\WikibaseClient::getDefaultInstance();
				$termIndex = $client->getStore()->getTermIndex();
				$entityIdLookup = $client->getStore()->getEntityIdLookup();
			}

			return new Wikibase\Client\Api\PageTerms(
				$termIndex,
				$entityIdLookup,
				$apiQuery,
				$moduleName
			);
		}
	);

	$wgAPIPropModules['description'] = [
		'class' => Wikibase\Client\Api\Description::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			$client = Wikibase\Client\WikibaseClient::getDefaultInstance();
			$allowLocalShortDesc = $client->getSettings()->getSetting( 'allowLocalShortDesc' );
			$descriptionLookup = $client->getStore()->getDescriptionLookup();
			return new Wikibase\Client\Api\Description(
				$apiQuery,
				$moduleName,
				$allowLocalShortDesc,
				$descriptionLookup
			);
		}
	];

	$wgAPIPropModules['wbentityusage'] = [
		'class' => Wikibase\Client\Api\ApiPropsEntityUsage::class,
		'factory' => function ( ApiQuery $query, $moduleName ) {
			$repoLinker = Wikibase\Client\WikibaseClient::getDefaultInstance()->newRepoLinker();
			return new Wikibase\Client\Api\ApiPropsEntityUsage(
				$query,
				$moduleName,
				$repoLinker
			);
		}
	];
	$wgAPIListModules['wblistentityusage'] = [
		'class' => Wikibase\Client\Api\ApiListEntityUsage::class,
		'factory' => function ( ApiQuery $apiQuery, $moduleName ) {
			return new Wikibase\Client\Api\ApiListEntityUsage(
				$apiQuery,
				$moduleName,
				Wikibase\Client\WikibaseClient::getDefaultInstance()->newRepoLinker()
			);
		}
	];
	$wgAPIUselessQueryPages[] = 'PagesWithBadges';

	// Special page registration
	$wgSpecialPages['UnconnectedPages'] = Wikibase\Client\Specials\SpecialUnconnectedPages::class;
	$wgSpecialPages['PagesWithBadges'] = function() {
		$wikibaseClient = Wikibase\Client\WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();
		return new Wikibase\Client\Specials\SpecialPagesWithBadges(
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
		return new Wikibase\Client\Specials\SpecialEntityUsage(
			Wikibase\Client\WikibaseClient::getDefaultInstance()->getEntityIdParser()
		);
	};

	$wgHooks['wgQueryPages'][] = 'Wikibase\ClientHooks::onwgQueryPages';

	// Resource loader modules
	$wgResourceModules = array_merge(
		$wgResourceModules,
		include __DIR__ . '/resources/Resources.php'
	);

	$wgWBClientSettings = array_merge(
		require __DIR__ . '/../lib/config/WikibaseLib.default.php',
		require __DIR__ . '/config/WikibaseClient.default.php'
	);

	$wgRecentChangesFlags['wikibase-edit'] = array(
		'letter' => 'wikibase-rc-wikibase-edit-letter',
		'title' => 'wikibase-rc-wikibase-edit-title',
		'legend' => 'wikibase-rc-wikibase-edit-legend',
		'grouping' => 'all',
	);
} );
