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

call_user_func( function() {
	global $wgExtensionCredits, $wgGroupPermissions, $wgGrantPermissions, $wgAvailableRights;
	global $wgExtensionMessagesFiles, $wgMessagesDirs;
	global $wgAPIModules, $wgAPIListModules, $wgSpecialPages, $wgHooks;
	global $wgWBRepoSettings, $wgResourceModules, $wgValueParsers, $wgJobClasses;
	global $wgWBRepoDataTypes, $wgWBRepoEntityTypes;

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
	$wgAPIModules['wbgetentities'] = Wikibase\Repo\Api\GetEntities::class;
	$wgAPIModules['wbsetlabel'] = Wikibase\Repo\Api\SetLabel::class;
	$wgAPIModules['wbsetdescription'] = Wikibase\Repo\Api\SetDescription::class;
	$wgAPIModules['wbsearchentities'] = Wikibase\Repo\Api\SearchEntities::class;
	$wgAPIModules['wbsetaliases'] = Wikibase\Repo\Api\SetAliases::class;
	$wgAPIModules['wbeditentity'] = Wikibase\Repo\Api\EditEntity::class;
	$wgAPIModules['wblinktitles'] = Wikibase\Repo\Api\LinkTitles::class;
	$wgAPIModules['wbsetsitelink'] = Wikibase\Repo\Api\SetSiteLink::class;
	$wgAPIModules['wbcreateclaim'] = Wikibase\Repo\Api\CreateClaim::class;
	$wgAPIModules['wbgetclaims'] = Wikibase\Repo\Api\GetClaims::class;
	$wgAPIModules['wbremoveclaims'] = Wikibase\Repo\Api\RemoveClaims::class;
	$wgAPIModules['wbsetclaimvalue'] = Wikibase\Repo\Api\SetClaimValue::class;
	$wgAPIModules['wbsetreference'] = Wikibase\Repo\Api\SetReference::class;
	$wgAPIModules['wbremovereferences'] = Wikibase\Repo\Api\RemoveReferences::class;
	$wgAPIModules['wbsetclaim'] = Wikibase\Repo\Api\SetClaim::class;
	$wgAPIModules['wbremovequalifiers'] = Wikibase\Repo\Api\RemoveQualifiers::class;
	$wgAPIModules['wbsetqualifier'] = Wikibase\Repo\Api\SetQualifier::class;
	$wgAPIModules['wbmergeitems'] = Wikibase\Repo\Api\MergeItems::class;
	$wgAPIModules['wbformatvalue'] = Wikibase\Repo\Api\FormatSnakValue::class;
	$wgAPIModules['wbparsevalue'] = Wikibase\Repo\Api\ParseValue::class;
	$wgAPIModules['wbavailablebadges'] = Wikibase\Repo\Api\AvailableBadges::class;
	$wgAPIModules['wbcreateredirect'] = Wikibase\Repo\Api\CreateRedirect::class;
	$wgAPIListModules['wbsearch'] = Wikibase\Repo\Api\QuerySearchEntities::class;

	// Special page registration
	$wgSpecialPages['NewItem'] = Wikibase\Repo\Specials\SpecialNewItem::class;
	$wgSpecialPages['NewProperty'] = Wikibase\Repo\Specials\SpecialNewProperty::class;
	$wgSpecialPages['ItemByTitle'] = Wikibase\Repo\Specials\SpecialItemByTitle::class;
	$wgSpecialPages['GoToLinkedPage'] = Wikibase\Repo\Specials\SpecialGoToLinkedPage::class;
	$wgSpecialPages['ItemDisambiguation'] = Wikibase\Repo\Specials\SpecialItemDisambiguation::class;
	$wgSpecialPages['ItemsWithoutSitelinks']
		= Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks::class;
	$wgSpecialPages['SetLabel'] = Wikibase\Repo\Specials\SpecialSetLabel::class;
	$wgSpecialPages['SetDescription'] = Wikibase\Repo\Specials\SpecialSetDescription::class;
	$wgSpecialPages['SetAliases'] = Wikibase\Repo\Specials\SpecialSetAliases::class;
	$wgSpecialPages['SetLabelDescriptionAliases']
		= Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases::class;
	$wgSpecialPages['SetSiteLink'] = Wikibase\Repo\Specials\SpecialSetSiteLink::class;
	$wgSpecialPages['EntitiesWithoutLabel'] = array(
		'Wikibase\Repo\Specials\SpecialEntitiesWithoutPageFactory',
		'newSpecialEntitiesWithoutLabel'
	);
	$wgSpecialPages['EntitiesWithoutDescription'] = array(
		'Wikibase\Repo\Specials\SpecialEntitiesWithoutPageFactory',
		'newSpecialEntitiesWithoutDescription'
	);
	$wgSpecialPages['ListDatatypes'] = Wikibase\Repo\Specials\SpecialListDatatypes::class;
	$wgSpecialPages['ListProperties'] = Wikibase\Repo\Specials\SpecialListProperties::class;
	$wgSpecialPages['DispatchStats'] = Wikibase\Repo\Specials\SpecialDispatchStats::class;
	$wgSpecialPages['EntityData'] = Wikibase\Repo\Specials\SpecialEntityData::class;
	$wgSpecialPages['MyLanguageFallbackChain']
		= Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain::class;
	$wgSpecialPages['MergeItems'] = Wikibase\Repo\Specials\SpecialMergeItems::class;
	$wgSpecialPages['RedirectEntity'] = Wikibase\Repo\Specials\SpecialRedirectEntity::class;

	// Jobs
	$wgJobClasses['UpdateRepoOnMove'] = Wikibase\Repo\UpdateRepo\UpdateRepoOnMoveJob::class;
	$wgJobClasses['UpdateRepoOnDelete'] = Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob::class;

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
