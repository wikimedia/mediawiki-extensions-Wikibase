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
use Wikibase\Client\Store\AddUsagesForPageJob;
use Wikibase\Client\WikibaseClient;
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
	$wgHooks['ContentAlterParserOutput'][] = '\Wikibase\Client\Hooks\ParserOutputUpdateHookHandlers::onContentAlterParserOutput';

	$wgHooks['ParserFirstCallInit'][] = '\Wikibase\Client\ClientHooks::onParserFirstCallInit';
	$wgHooks['GetPreferences'][] = '\Wikibase\Client\ClientHooks::onGetPreferences';
	$wgHooks['BeforePageDisplay'][] = '\Wikibase\Client\ClientHooks::onBeforePageDisplay';
	$wgHooks['BeforePageDisplay'][] = '\Wikibase\Client\ClientHooks::onBeforePageDisplayAddJsConfig';
	$wgHooks['ScribuntoExternalLibraries'][] = '\Wikibase\Client\ClientHooks::onScribuntoExternalLibraries';
	$wgHooks['EditPage::showStandardInputs:options'][] = '\Wikibase\Client\ClientHooks::onEditAction';
	$wgHooks['SkinAfterPortlet'][] = '\Wikibase\Client\ClientHooks::onSkinAfterPortlet';
	$wgHooks['ArticleDeleteAfterSuccess'][] = '\Wikibase\Client\ClientHooks::onArticleDeleteAfterSuccess';
	$wgHooks['FormatAutocomments'][] = '\Wikibase\Client\ClientHooks::onFormat';
	$wgHooks['AbortEmailNotification'][] = '\Wikibase\Client\ClientHooks::onAbortEmailNotification';
	$wgHooks['SearchDataForIndex'][] = '\Wikibase\Client\ClientHooks::onSearchDataForIndex';
	$wgHooks['SearchIndexFields'][] = '\Wikibase\Client\ClientHooks::onSearchIndexFields';

	$wgHooks['CirrusSearchAddQueryFeatures'][] = '\Wikibase\Client\ClientHooks::onCirrusSearchAddQueryFeatures';

	$wgHooks['SkinAfterBottomScripts'][] = '\Wikibase\Client\ClientHooks::onSkinAfterBottomScripts';

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
			if ( ExtensionRegistry::getInstance()->isLoaded( 'WikibaseRepository' ) ) {
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
