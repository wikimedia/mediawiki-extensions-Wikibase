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
 * out more at https://wikiba.se               \/        \/
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

use MediaWiki\MediaWikiServices;
use Wikibase\Client\Api\ApiClientInfo;
use Wikibase\Client\Api\ApiListEntityUsage;
use Wikibase\Client\Api\ApiPropsEntityUsage;
use Wikibase\Client\Api\Description;
use Wikibase\Client\Api\PageTerms;
use Wikibase\Client\ChangeNotificationJob;
use Wikibase\Client\Changes\InjectRCRecordsJob;
use Wikibase\Client\ChangeVisibilityNotificationJob;
use Wikibase\Client\Specials\SpecialEntityUsage;
use Wikibase\Client\Specials\SpecialPagesWithBadges;
use Wikibase\Client\Specials\SpecialUnconnectedPages;
use Wikibase\Client\Store\AddUsagesForPageJob;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\WikibaseRepo;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not an entry point.\n" );
}

define( 'WBC_VERSION', '0.5 alpha' );

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseClient', __DIR__ . '/../extension-client-wip.json' );

// Sub-extensions needed by WikibaseClient
require_once __DIR__ . '/../lib/WikibaseLib.php';

call_user_func( function() {
	global $wgAPIListModules,
		$wgAPIMetaModules,
		$wgAPIPropModules,
		$wgExtensionFunctions,
		$wgExtensionMessagesFiles,
		$wgHooks,
		$wgJobClasses,
		$wgMessagesDirs,
		$wgRecentChangesFlags,
		$wgResourceModules,
		$wgSpecialPages,
		$wgWBClientDataTypes,
		$wgWBClientSettings;

	// Registry and definition of data types
	$wgWBClientDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
	$clientDatatypes = require __DIR__ . '/WikibaseClient.datatypes.php';

	// merge WikibaseClient.datatypes.php into $wgWBClientDataTypes
	foreach ( $clientDatatypes as $type => $clientDef ) {
		$baseDef = $wgWBClientDataTypes[$type] ?? [];
		$wgWBClientDataTypes[$type] = array_merge( $baseDef, $clientDef );
	}

	// i18n
	$wgMessagesDirs['wikibaseclient'] = __DIR__ . '/i18n';
	$wgMessagesDirs['wikibaseclientapi'] = __DIR__ . '/i18n/api';
	$wgExtensionMessagesFiles['Wikibaseclientalias'] = __DIR__ . '/WikibaseClient.i18n.alias.php';
	$wgExtensionMessagesFiles['wikibaseclientmagic'] = __DIR__ . '/WikibaseClient.i18n.magic.php';

	$wgHooks['UnitTestsList'][] = '\Wikibase\Client\ClientHooks::registerUnitTests';
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

	$wgHooks['ParserFirstCallInit'][] = '\Wikibase\Client\ClientHooks::onParserFirstCallInit';
	$wgHooks['SpecialMovepageAfterMove'][] = '\Wikibase\Client\Hooks\MovePageNotice::onSpecialMovepageAfterMove';
	$wgHooks['GetPreferences'][] = '\Wikibase\Client\ClientHooks::onGetPreferences';
	$wgHooks['BeforePageDisplay'][] = '\Wikibase\Client\ClientHooks::onBeforePageDisplay';
	$wgHooks['BeforePageDisplay'][] = '\Wikibase\Client\ClientHooks::onBeforePageDisplayAddJsConfig';
	$wgHooks['ScribuntoExternalLibraries'][] = '\Wikibase\Client\ClientHooks::onScribuntoExternalLibraries';
	$wgHooks['InfoAction'][] = '\Wikibase\Client\Hooks\InfoActionHookHandler::onInfoAction';
	$wgHooks['EditPage::showStandardInputs:options'][] = '\Wikibase\Client\ClientHooks::onEditAction';
	$wgHooks['SkinAfterPortlet'][] = '\Wikibase\Client\ClientHooks::onSkinAfterPortlet';
	$wgHooks['ArticleDeleteAfterSuccess'][] = '\Wikibase\Client\ClientHooks::onArticleDeleteAfterSuccess';
	$wgHooks['ParserLimitReportPrepare'][] = '\Wikibase\Client\Hooks\ParserLimitReportPrepareHookHandler::onParserLimitReportPrepare';
	$wgHooks['FormatAutocomments'][] = '\Wikibase\Client\ClientHooks::onFormat';
	$wgHooks['ParserClearState'][] = '\Wikibase\Client\Hooks\ParserClearStateHookHandler::onParserClearState';
	$wgHooks['AbortEmailNotification'][] = '\Wikibase\Client\ClientHooks::onAbortEmailNotification';
	$wgHooks['SearchDataForIndex'][] = '\Wikibase\Client\ClientHooks::onSearchDataForIndex';
	$wgHooks['SearchIndexFields'][] = '\Wikibase\Client\ClientHooks::onSearchIndexFields';

	$wgHooks['CirrusSearchAddQueryFeatures'][] = '\Wikibase\Client\ClientHooks::onCirrusSearchAddQueryFeatures';

	$wgHooks['SkinAfterBottomScripts'][] = '\Wikibase\Client\ClientHooks::onSkinAfterBottomScripts';

	// for client notifications (requires the Echo extension)
	// note that Echo calls BeforeCreateEchoEvent hook when it is being initialized,
	// thus we have to register these two handlers disregarding Echo is loaded or not
	$wgHooks['BeforeCreateEchoEvent'][] = '\Wikibase\Client\Hooks\EchoSetupHookHandlers::onBeforeCreateEchoEvent';
	$wgHooks['EchoGetBundleRules'][] = '\Wikibase\Client\Hooks\EchoNotificationsHandlers::onEchoGetBundleRules';

	// conditionally register the remaining two handlers which would otherwise fail
	$wgExtensionFunctions[] = '\Wikibase\Client\Hooks\ExtensionLoadHandler::onExtensionLoad';

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

	// job classes
	$wgJobClasses['wikibase-addUsagesForPage'] = AddUsagesForPageJob::class;
	$wgJobClasses['ChangeNotification'] = ChangeNotificationJob::class;
	$wgJobClasses['ChangeVisibilityNotification'] = function ( Title $unused, array $params ) {
		return new ChangeVisibilityNotificationJob(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			$params
		);
	};
	$wgJobClasses['wikibase-InjectRCRecords'] = function ( Title $unused, array $params ) {
		$mwServices = MediaWikiServices::getInstance();
		$wbServices = WikibaseClient::getDefaultInstance();

		$job = new InjectRCRecordsJob(
			$mwServices->getDBLoadBalancerFactory(),
			$wbServices->getStore()->getEntityChangeLookup(),
			$wbServices->getEntityChangeFactory(),
			$wbServices->getRecentChangeFactory(),
			$params
		);

		$job->setRecentChangesFinder( $wbServices->getStore()->getRecentChangesFinder() );

		$job->setLogger( $wbServices->getLogger() );
		$job->setStats( $mwServices->getStatsdDataFactory() );

		return $job;
	};

	// api modules
	$wgAPIMetaModules['wikibase'] = [
		'class' => ApiClientInfo::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			return new ApiClientInfo(
				WikibaseClient::getDefaultInstance()->getSettings(),
				$apiQuery,
				$moduleName
			);
		}
	];

	$wgAPIPropModules['pageterms'] = [
		'class' => PageTerms::class,
		'factory' => function ( ApiQuery $apiQuery, $moduleName ) {
			// FIXME: HACK: make pageterms work directly on entity pages on the repo.
			// We should instead use an EntityIdLookup that combines the repo and the client
			// implementation, see T115117.
			// NOTE: when changing repo and/or client integration, remember to update the
			// self-documentation of the API module in the "apihelp-query+pageterms-description"
			// message and the PageTerms::getExamplesMessages() method.
			if ( defined( 'WB_VERSION' ) ) {
				$repo = WikibaseRepo::getDefaultInstance();
				$termBuffer = $repo->getTermBuffer();
				$entityIdLookup = $repo->getEntityContentFactory();
			} else {
				$client = WikibaseClient::getDefaultInstance();
				$termBuffer = $client->getTermBuffer();
				$entityIdLookup = $client->getEntityIdLookup();
			}

			return new PageTerms(
				$termBuffer,
				$entityIdLookup,
				$apiQuery,
				$moduleName
			);
		}
	];

	$wgAPIPropModules['description'] = [
		'class' => Description::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			$client = WikibaseClient::getDefaultInstance();
			$allowLocalShortDesc = $client->getSettings()->getSetting( 'allowLocalShortDesc' );
			$descriptionLookup = $client->getDescriptionLookup();
			return new Description(
				$apiQuery,
				$moduleName,
				$allowLocalShortDesc,
				$descriptionLookup
			);
		}
	];

	$wgAPIPropModules['wbentityusage'] = [
		'class' => ApiPropsEntityUsage::class,
		'factory' => function ( ApiQuery $query, $moduleName ) {
			$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();
			return new ApiPropsEntityUsage(
				$query,
				$moduleName,
				$repoLinker
			);
		}
	];
	$wgAPIListModules['wblistentityusage'] = [
		'class' => ApiListEntityUsage::class,
		'factory' => function ( ApiQuery $apiQuery, $moduleName ) {
			return new ApiListEntityUsage(
				$apiQuery,
				$moduleName,
				WikibaseClient::getDefaultInstance()->newRepoLinker()
			);
		}
	];

	// Special page registration
	$wgSpecialPages['UnconnectedPages'] = SpecialUnconnectedPages::class;
	$wgSpecialPages['PagesWithBadges'] = function() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();
		return new SpecialPagesWithBadges(
			new LanguageFallbackLabelDescriptionLookupFactory(
				$wikibaseClient->getLanguageFallbackChainFactory(),
				$wikibaseClient->getTermLookup(),
				$wikibaseClient->getTermBuffer()
			),
			array_keys( $settings->getSetting( 'badgeClassNames' ) ),
			$settings->getSetting( 'siteGlobalID' )
		);
	};
	$wgSpecialPages['EntityUsage'] = function () {
		return new SpecialEntityUsage(
			WikibaseClient::getDefaultInstance()->getEntityIdParser()
		);
	};

	$wgHooks['wgQueryPages'][] = '\Wikibase\Client\ClientHooks::onwgQueryPages';

	// Resource loader modules
	$wgResourceModules = array_merge(
		$wgResourceModules,
		require __DIR__ . '/resources/Resources.php'
	);

	$wgWBClientSettings = array_merge(
		require __DIR__ . '/../lib/config/WikibaseLib.default.php',
		require __DIR__ . '/config/WikibaseClient.default.php'
	);
} );
