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

use Wikibase\Repo\Api\AvailableBadges;
use Wikibase\Repo\Api\CreateClaim;
use Wikibase\Repo\Api\CreateRedirect;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\Api\FormatSnakValue;
use Wikibase\Repo\Api\GetClaims;
use Wikibase\Repo\Api\GetEntities;
use Wikibase\Repo\Api\LinkTitles;
use Wikibase\Repo\Api\MergeItems;
use Wikibase\Repo\Api\ParseValue;
use Wikibase\Repo\Api\QuerySearchEntities;
use Wikibase\Repo\Api\RemoveClaims;
use Wikibase\Repo\Api\RemoveQualifiers;
use Wikibase\Repo\Api\RemoveReferences;
use Wikibase\Repo\Api\SearchEntities;
use Wikibase\Repo\Api\SetAliases;
use Wikibase\Repo\Api\SetClaim;
use Wikibase\Repo\Api\SetClaimValue;
use Wikibase\Repo\Api\SetDescription;
use Wikibase\Repo\Api\SetLabel;
use Wikibase\Repo\Api\SetQualifier;
use Wikibase\Repo\Api\SetReference;
use Wikibase\Repo\Api\SetSiteLink;
use Wikibase\Repo\Specials\SpecialDispatchStats;
use Wikibase\Repo\Specials\SpecialEntitiesWithoutPageFactory;
use Wikibase\Repo\Specials\SpecialEntityData;
use Wikibase\Repo\Specials\SpecialGoToLinkedPage;
use Wikibase\Repo\Specials\SpecialItemByTitle;
use Wikibase\Repo\Specials\SpecialItemDisambiguation;
use Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks;
use Wikibase\Repo\Specials\SpecialListDatatypes;
use Wikibase\Repo\Specials\SpecialListProperties;
use Wikibase\Repo\Specials\SpecialMergeItems;
use Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain;
use Wikibase\Repo\Specials\SpecialNewItem;
use Wikibase\Repo\Specials\SpecialNewProperty;
use Wikibase\Repo\Specials\SpecialRedirectEntity;
use Wikibase\Repo\Specials\SpecialSetAliases;
use Wikibase\Repo\Specials\SpecialSetDescription;
use Wikibase\Repo\Specials\SpecialSetLabel;
use Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases;
use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob;
use Wikibase\Repo\UpdateRepo\UpdateRepoOnMoveJob;

/**
 * Entry point for the Wikibase Repository extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Repository
 *
 * @license GPL-2.0+
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( defined( 'WB_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'WB_VERSION', '0.5 alpha' );

// Needs to be 1.26c because version_compare() works in confusing ways.
if ( version_compare( $GLOBALS['wgVersion'], '1.26c', '<' ) ) {
	die( "<b>Error:</b> Wikibase requires MediaWiki 1.26 or above.\n" );
}

/**
 * Registry of ValueParsers classes or factory callbacks, by datatype.
 * @note: that parsers are also registered under their old names for backwards compatibility,
 * for use with the deprecated 'parser' parameter of the wbparsevalue API module.
 */
$GLOBALS['wgValueParsers'] = array();

// Include the WikibaseLib extension if that hasn't been done yet, since it's required for Wikibase to work.
if ( !defined( 'WBL_VERSION' ) ) {
	include_once __DIR__ . '/../lib/WikibaseLib.php';
}

if ( !defined( 'WBL_VERSION' ) ) {
	throw new Exception( 'Wikibase depends on the WikibaseLib extension.' );
}

if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
	include_once __DIR__ . '/../view/WikibaseView.php';
}

if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
	throw new Exception( 'Wikibase depends on WikibaseView.' );
}

if ( !defined( 'PURTLE_VERSION' ) ) {
	include_once __DIR__ . '/../purtle/Purtle.php';
}

if ( !defined( 'PURTLE_VERSION' ) ) {
	throw new Exception( 'Wikibase depends on Purtle.' );
}

call_user_func( function() {
	global $wgExtensionCredits, $wgGroupPermissions, $wgGrantPermissions, $wgAvailableRights;
	global $wgExtensionMessagesFiles, $wgMessagesDirs;
	global $wgAPIModules, $wgAPIListModules, $wgSpecialPages, $wgHooks;
	global $wgWBRepoSettings, $wgResourceModules, $wgValueParsers, $wgJobClasses;
	global $wgWBRepoDataTypes, $wgWBRepoEntityTypes, $wgContentHandlers;

	$wgExtensionCredits['wikibase'][] = array(
		'path' => __DIR__,
		'name' => 'Wikibase Repository',
		'version' => WB_VERSION,
		'author' => array(
			'The Wikidata team',
		),
		'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase',
		'descriptionmsg' => 'wikibase-desc',
		'license-name' => 'GPL-2.0+'
	);

	// Registry and definition of data types
	$wgWBRepoDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';

	$repoDataTypes = require __DIR__ . '/WikibaseRepo.datatypes.php';

	// merge WikibaseRepo.datatypes.php into $wgWBRepoDataTypes
	foreach ( $repoDataTypes as $type => $repoDef ) {
		$baseDef = isset( $wgWBRepoDataTypes[$type] ) ? $wgWBRepoDataTypes[$type] : array();
		$wgWBRepoDataTypes[$type] = array_merge( $baseDef, $repoDef );
	}

	// constants
	define( 'CONTENT_MODEL_WIKIBASE_ITEM', "wikibase-item" );
	define( 'CONTENT_MODEL_WIKIBASE_PROPERTY', "wikibase-property" );

	// Registry and definition of entity types
	$wgWBRepoEntityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';

	$repoEntityTypes = require __DIR__ . '/WikibaseRepo.entitytypes.php';

	// merge WikibaseRepo.entitytypes.php into $wgWBRepoEntityTypes
	foreach ( $repoEntityTypes as $type => $repoDef ) {
		$baseDef = isset( $wgWBRepoEntityTypes[$type] ) ? $wgWBRepoEntityTypes[$type] : array();
		$wgWBRepoEntityTypes[$type] = array_merge( $baseDef, $repoDef );
	}

	// rights
	// names should be according to other naming scheme
	$wgGroupPermissions['*']['item-term'] = true;
	$wgGroupPermissions['*']['property-term'] = true;
	$wgGroupPermissions['*']['item-merge'] = true;
	$wgGroupPermissions['*']['item-redirect'] = true;
	$wgGroupPermissions['*']['property-create'] = true;

	$wgAvailableRights[] = 'item-term';
	$wgAvailableRights[] = 'property-term';
	$wgAvailableRights[] = 'item-merge';
	$wgAvailableRights[] = 'item-redirect';
	$wgAvailableRights[] = 'property-create';

	$wgGrantPermissions['editpage']['item-term'] = true;
	$wgGrantPermissions['editpage']['item-redirect'] = true;
	$wgGrantPermissions['editpage']['item-merge'] = true;
	$wgGrantPermissions['editpage']['property-term'] = true;
	$wgGrantPermissions['createeditmovepage']['property-create'] = true;

	// i18n
	$wgMessagesDirs['Wikibase'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikibaseAlias'] = __DIR__ . '/Wikibase.i18n.alias.php';
	$wgExtensionMessagesFiles['WikibaseNS'] = __DIR__ . '/Wikibase.i18n.namespaces.php';

	/**
	 * @var callable[] $wgValueParsers Defines parser factory callbacks by parser name (not data type name).
	 * @deprecated use $wgWBRepoDataTypes instead.
	 */
	$wgValueParsers['wikibase-entityid'] = $wgWBRepoDataTypes['VT:wikibase-entityid']['parser-factory-callback'];
	$wgValueParsers['globecoordinate'] = $wgWBRepoDataTypes['VT:globecoordinate']['parser-factory-callback'];

	// 'null' is not a datatype. Kept for backwards compatibility.
	$wgValueParsers['null'] = function() {
		return new \ValueParsers\NullParser();
	};

	// API module registration
	$wgAPIModules['wbgetentities'] = GetEntities::class;
	$wgAPIModules['wbsetlabel'] = SetLabel::class;
	$wgAPIModules['wbsetdescription'] = SetDescription::class;
	$wgAPIModules['wbsearchentities'] = SearchEntities::class;
	$wgAPIModules['wbsetaliases'] = SetAliases::class;
	$wgAPIModules['wbeditentity'] = EditEntity::class;
	$wgAPIModules['wblinktitles'] = LinkTitles::class;
	$wgAPIModules['wbsetsitelink'] = SetSiteLink::class;
	$wgAPIModules['wbcreateclaim'] = CreateClaim::class;
	$wgAPIModules['wbgetclaims'] = GetClaims::class;
	$wgAPIModules['wbremoveclaims'] = RemoveClaims::class;
	$wgAPIModules['wbsetclaimvalue'] = SetClaimValue::class;
	$wgAPIModules['wbsetreference'] = SetReference::class;
	$wgAPIModules['wbremovereferences'] = RemoveReferences::class;
	$wgAPIModules['wbsetclaim'] = SetClaim::class;
	$wgAPIModules['wbremovequalifiers'] = RemoveQualifiers::class;
	$wgAPIModules['wbsetqualifier'] = SetQualifier::class;
	$wgAPIModules['wbmergeitems'] = MergeItems::class;
	$wgAPIModules['wbformatvalue'] = FormatSnakValue::class;
	$wgAPIModules['wbparsevalue'] = ParseValue::class;
	$wgAPIModules['wbavailablebadges'] = AvailableBadges::class;
	$wgAPIModules['wbcreateredirect'] = CreateRedirect::class;
	$wgAPIListModules['wbsearch'] = QuerySearchEntities::class;

	// Special page registration
	$wgSpecialPages['NewItem'] = SpecialNewItem::class;
	$wgSpecialPages['NewProperty'] = SpecialNewProperty::class;
	$wgSpecialPages['ItemByTitle'] = SpecialItemByTitle::class;
	$wgSpecialPages['GoToLinkedPage'] = SpecialGoToLinkedPage::class;
	$wgSpecialPages['ItemDisambiguation'] = SpecialItemDisambiguation::class;
	$wgSpecialPages['ItemsWithoutSitelinks'] = SpecialItemsWithoutSitelinks::class;
	$wgSpecialPages['SetLabel'] = SpecialSetLabel::class;
	$wgSpecialPages['SetDescription'] = SpecialSetDescription::class;
	$wgSpecialPages['SetAliases'] = SpecialSetAliases::class;
	$wgSpecialPages['SetLabelDescriptionAliases'] = SpecialSetLabelDescriptionAliases::class;
	$wgSpecialPages['SetSiteLink'] = SpecialSetSiteLink::class;
	$wgSpecialPages['EntitiesWithoutLabel'] = array(
		SpecialEntitiesWithoutPageFactory::class,
		'newSpecialEntitiesWithoutLabel'
	);
	$wgSpecialPages['EntitiesWithoutDescription'] = [
		SpecialEntitiesWithoutPageFactory::class,
		'newSpecialEntitiesWithoutDescription'
	];
	$wgSpecialPages['ListDatatypes'] = SpecialListDatatypes::class;
	$wgSpecialPages['ListProperties'] = SpecialListProperties::class;
	$wgSpecialPages['DispatchStats'] = SpecialDispatchStats::class;
	$wgSpecialPages['EntityData'] = SpecialEntityData::class;
	$wgSpecialPages['MyLanguageFallbackChain'] = SpecialMyLanguageFallbackChain::class;
	$wgSpecialPages['MergeItems'] = SpecialMergeItems::class;
	$wgSpecialPages['RedirectEntity'] = SpecialRedirectEntity::class;

	// Jobs
	$wgJobClasses['UpdateRepoOnMove'] = UpdateRepoOnMoveJob::class;
	$wgJobClasses['UpdateRepoOnDelete'] = UpdateRepoOnDeleteJob::class;

	// Hooks
	$wgHooks['BeforePageDisplay'][] = 'Wikibase\RepoHooks::onBeforePageDisplay';
	$wgHooks['LoadExtensionSchemaUpdates'][] = 'Wikibase\Repo\Store\Sql\DatabaseSchemaUpdater::onSchemaUpdate';
	$wgHooks['UnitTestsList'][] = 'Wikibase\RepoHooks::registerUnitTests';
	$wgHooks['ResourceLoaderTestModules'][] = 'Wikibase\RepoHooks::registerQUnitTests';

	$wgHooks['NamespaceIsMovable'][] = 'Wikibase\RepoHooks::onNamespaceIsMovable';
	$wgHooks['NewRevisionFromEditComplete'][] = 'Wikibase\RepoHooks::onNewRevisionFromEditComplete';
	$wgHooks['SkinTemplateNavigation'][] = 'Wikibase\RepoHooks::onPageTabs';
	$wgHooks['RecentChange_save'][] = 'Wikibase\RepoHooks::onRecentChangeSave';
	$wgHooks['ArticleDeleteComplete'][] = 'Wikibase\RepoHooks::onArticleDeleteComplete';
	$wgHooks['ArticleUndelete'][] = 'Wikibase\RepoHooks::onArticleUndelete';
	$wgHooks['GetPreferences'][] = 'Wikibase\RepoHooks::onGetPreferences';
	$wgHooks['LinkBegin'][] = 'Wikibase\Repo\Hooks\LinkBeginHookHandler::onLinkBegin';
	$wgHooks['ChangesListInitRows'][] = 'Wikibase\Repo\Hooks\LabelPrefetchHookHandlers::onChangesListInitRows';
	$wgHooks['OutputPageBodyAttributes'][] = 'Wikibase\RepoHooks::onOutputPageBodyAttributes';
	//FIXME: handle other types of entities with autocomments too!
	$wgHooks['FormatAutocomments'][] = array(
		'Wikibase\RepoHooks::onFormat',
		array( CONTENT_MODEL_WIKIBASE_ITEM, 'wikibase-item' )
	);
	$wgHooks['FormatAutocomments'][] = array(
		'Wikibase\RepoHooks::onFormat',
		array( CONTENT_MODEL_WIKIBASE_PROPERTY, 'wikibase-property' )
	);
	$wgHooks['PageHistoryLineEnding'][] = 'Wikibase\RepoHooks::onPageHistoryLineEnding';
	$wgHooks['ApiCheckCanExecute'][] = 'Wikibase\RepoHooks::onApiCheckCanExecute';
	$wgHooks['SetupAfterCache'][] = 'Wikibase\RepoHooks::onSetupAfterCache';
	$wgHooks['ShowSearchHit'][] = 'Wikibase\RepoHooks::onShowSearchHit';
	$wgHooks['ShowSearchHitTitle'][] = 'Wikibase\RepoHooks::onShowSearchHitTitle';
	$wgHooks['TitleGetRestrictionTypes'][] = 'Wikibase\RepoHooks::onTitleGetRestrictionTypes';
	$wgHooks['TitleQuickPermissions'][] = 'Wikibase\RepoHooks::onTitleQuickPermissions';
	$wgHooks['AbuseFilter-contentToString'][] = 'Wikibase\RepoHooks::onAbuseFilterContentToString';
	$wgHooks['SpecialPage_reorderPages'][] = 'Wikibase\RepoHooks::onSpecialPageReorderPages';
	$wgHooks['OutputPageParserOutput'][] = 'Wikibase\RepoHooks::onOutputPageParserOutput';
	$wgHooks['ContentModelCanBeUsedOn'][] = 'Wikibase\RepoHooks::onContentModelCanBeUsedOn';
	$wgHooks['OutputPageBeforeHTML'][] = 'Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler::onOutputPageBeforeHTML';
	$wgHooks['OutputPageBeforeHTML'][] = 'Wikibase\Repo\Hooks\OutputPageJsConfigHookHandler::onOutputPageBeforeHtmlRegisterConfig';
	$wgHooks['APIQuerySiteInfoGeneralInfo'][] = 'Wikibase\RepoHooks::onAPIQuerySiteInfoGeneralInfo';
	$wgHooks['APIQuerySiteInfoStatisticsInfo'][] = 'Wikibase\RepoHooks::onAPIQuerySiteInfoStatisticsInfo';
	$wgHooks['ImportHandleRevisionXMLTag'][] = 'Wikibase\RepoHooks::onImportHandleRevisionXMLTag';
	$wgHooks['BaseTemplateToolbox'][] = 'Wikibase\RepoHooks::onBaseTemplateToolbox';
	$wgHooks['SkinTemplateBuildNavUrlsNav_urlsAfterPermalink'][] = 'Wikibase\RepoHooks::onSkinTemplateBuildNavUrlsNavUrlsAfterPermalink';
	$wgHooks['SkinMinervaDefaultModules'][] = 'Wikibase\RepoHooks::onSkinMinervaDefaultModules';
	$wgHooks['ResourceLoaderRegisterModules'][] = 'Wikibase\RepoHooks::onResourceLoaderRegisterModules';
	$wgHooks['ContentHandlerForModelID'][] = 'Wikibase\RepoHooks::onContentHandlerForModelID';

	// CirrusSearch hooks
	$wgHooks['CirrusSearchMappingConfig'][] = 'Wikibase\Repo\Hooks\CirrusSearchHookHandlers::onCirrusSearchMappingConfig';
	$wgHooks['CirrusSearchBuildDocumentParse'][] = 'Wikibase\Repo\Hooks\CirrusSearchHookHandlers::onCirrusSearchBuildDocumentParse';

	// update hooks
	$wgHooks['LoadExtensionSchemaUpdates'][] = '\Wikibase\Repo\Store\Sql\ChangesSubscriptionSchemaUpdater::onSchemaUpdate';

	// Resource Loader Modules:
	$wgResourceModules = array_merge(
		$wgResourceModules,
		include __DIR__ . '/resources/Resources.php'
	);

	$wgWBRepoSettings = array_merge(
		require __DIR__ . '/../lib/config/WikibaseLib.default.php',
		require __DIR__ . '/config/Wikibase.default.php'
	);
} );
