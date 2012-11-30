<?php

/**
 * Initialization file for the Wikibase extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:Wikibase
 * Support					https://www.mediawiki.org/wiki/Extension_talk:Wikibase
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikidataRepo.git
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 * @author Daniel Kinzler
 */

/**
 * This documentation group collects source code files belonging to Wikibase Repository.
 *
 * @defgroup WikibaseRepo Wikibase Repo
 * @ingroup Wikibase
 */

/**
 * This documentation group collects source code files with tests for Wikibase Repository.
 *
 * @defgroup WikibaseRepoTest Tests for Wikibase Repo
 * @ingroup WikibaseRepo
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.20c', '<' ) ) { // Needs to be 1.20c because version_compare() works in confusing ways.
	die( '<b>Error:</b> Wikibase requires MediaWiki 1.20 or above.' );
}

// Include the WikibaseLib extension if that hasn't been done yet, since it's required for Wikibase to work.
if ( !defined( 'WBL_VERSION' ) ) {
	@include_once( __DIR__ . '/../lib/WikibaseLib.php' );
}

if ( !defined( 'WBL_VERSION' ) ) {
	die( '<b>Error:</b> Wikibase depends on the <a href="https://www.mediawiki.org/wiki/Extension:WikibaseLib">WikibaseLib</a> extension.' );
}

define( 'WB_VERSION', '0.3 alpha' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase',
	'version' => WB_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase',
	'descriptionmsg' => 'wikibase-desc'
);

// constants
define( 'CONTENT_MODEL_WIKIBASE_ITEM', "wikibase-item" );
define( 'CONTENT_MODEL_WIKIBASE_PROPERTY', "wikibase-property" );
define( 'CONTENT_MODEL_WIKIBASE_QUERY', "wikibase-query" );

$dir = __DIR__ . '/';

// rights
// names should be according to other naming scheme
$wgGroupPermissions['*']['item-override']	= true;
$wgGroupPermissions['*']['item-create']		= true;
$wgGroupPermissions['*']['item-remove']		= true;
$wgGroupPermissions['*']['alias-add']		= true;
$wgGroupPermissions['*']['alias-set']		= true;
$wgGroupPermissions['*']['alias-remove']	= true;
$wgGroupPermissions['*']['sitelink-remove']	= true;
$wgGroupPermissions['*']['sitelink-update']	= true;
$wgGroupPermissions['*']['linktitles-update']	= true;
$wgGroupPermissions['*']['label-remove']	= true;
$wgGroupPermissions['*']['label-update']	= true;
$wgGroupPermissions['*']['description-remove']	= true;
$wgGroupPermissions['*']['description-update']	= true;

// i18n
$wgExtensionMessagesFiles['Wikibase'] 		= $dir . 'Wikibase.i18n.php';
$wgExtensionMessagesFiles['WikibaseAlias'] 	= $dir . 'Wikibase.i18n.alias.php';
$wgExtensionMessagesFiles['WikibaseNS'] 	= $dir . 'Wikibase.i18n.namespaces.php';


// Autoloading
$wgAutoloadClasses['Wikibase\RepoHooks'] 				= $dir . 'Wikibase.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\Autocomment']				= $dir . 'includes/Autocomment.php';
$wgAutoloadClasses['Wikibase\EditEntity'] 				= $dir . 'includes/EditEntity.php';
$wgAutoloadClasses['Wikibase\EntityContentDiffView'] 	= $dir . 'includes/EntityContentDiffView.php';
$wgAutoloadClasses['Wikibase\ItemContentDiffView'] 		= $dir . 'includes/ItemContentDiffView.php';
$wgAutoloadClasses['Wikibase\ItemDisambiguation'] 		= $dir . 'includes/ItemDisambiguation.php';
$wgAutoloadClasses['Wikibase\EntityView']				= $dir . 'includes/EntityView.php';
$wgAutoloadClasses['Wikibase\ItemView'] 				= $dir . 'includes/ItemView.php';
$wgAutoloadClasses['Wikibase\PropertyView']				= $dir . 'includes/PropertyView.php';

// includes/actions
$wgAutoloadClasses['Wikibase\HistoryEntityAction'] 		= $dir . 'includes/actions/HistoryEntityAction.php';
$wgAutoloadClasses['Wikibase\HistoryItemAction'] 		= $dir . 'includes/actions/HistoryItemAction.php';
$wgAutoloadClasses['Wikibase\HistoryPropertyAction'] 		= $dir . 'includes/actions/HistoryPropertyAction.php';
$wgAutoloadClasses['Wikibase\HistoryQueryAction'] 		= $dir . 'includes/actions/HistoryQueryAction.php';
$wgAutoloadClasses['Wikibase\EditEntityAction'] 		= $dir . 'includes/actions/EditEntityAction.php';
$wgAutoloadClasses['Wikibase\EditItemAction'] 			= $dir . 'includes/actions/EditItemAction.php';
$wgAutoloadClasses['Wikibase\EditPropertyAction'] 		= $dir . 'includes/actions/EditPropertyAction.php';
$wgAutoloadClasses['Wikibase\EditQueryAction'] 			= $dir . 'includes/actions/EditQueryAction.php';
$wgAutoloadClasses['Wikibase\ViewEntityAction'] 		= $dir . 'includes/actions/ViewEntityAction.php';
$wgAutoloadClasses['Wikibase\ViewItemAction'] 			= $dir . 'includes/actions/ViewItemAction.php';
$wgAutoloadClasses['Wikibase\ViewPropertyAction'] 		= $dir . 'includes/actions/ViewPropertyAction.php';
$wgAutoloadClasses['Wikibase\ViewQueryAction'] 			= $dir . 'includes/actions/ViewQueryAction.php';
$wgAutoloadClasses['Wikibase\SubmitEntityAction'] 		= $dir . 'includes/actions/EditEntityAction.php';
$wgAutoloadClasses['Wikibase\SubmitItemAction'] 		= $dir . 'includes/actions/EditItemAction.php';
$wgAutoloadClasses['Wikibase\SubmitPropertyAction'] 	= $dir . 'includes/actions/EditPropertyAction.php';
$wgAutoloadClasses['Wikibase\SubmitQueryAction'] 		= $dir . 'includes/actions/EditQueryAction.php';

// includes/api
$wgAutoloadClasses['Wikibase\Api'] 						= $dir . 'includes/api/Api.php';
$wgAutoloadClasses['Wikibase\ApiAutocomment'] 			= $dir . 'includes/api/ApiAutocomment.php';
$wgAutoloadClasses['Wikibase\ApiEditEntity'] 			= $dir . 'includes/api/ApiEditEntity.php';
$wgAutoloadClasses['Wikibase\ApiGetEntities'] 			= $dir . 'includes/api/ApiGetEntities.php';
$wgAutoloadClasses['Wikibase\ApiLinkTitles'] 			= $dir . 'includes/api/ApiLinkTitles.php';
$wgAutoloadClasses['Wikibase\ApiModifyEntity'] 			= $dir . 'includes/api/ApiModifyEntity.php';
$wgAutoloadClasses['Wikibase\ApiModifyLangAttribute'] 	= $dir . 'includes/api/ApiModifyLangAttribute.php';
$wgAutoloadClasses['Wikibase\ApiSearchEntities'] 		= $dir . 'includes/api/ApiSearchEntities.php';
$wgAutoloadClasses['Wikibase\ApiSetAliases'] 			= $dir . 'includes/api/ApiSetAliases.php';
$wgAutoloadClasses['Wikibase\ApiSetDescription'] 		= $dir . 'includes/api/ApiSetDescription.php';
$wgAutoloadClasses['Wikibase\ApiSetLabel'] 				= $dir . 'includes/api/ApiSetLabel.php';
$wgAutoloadClasses['Wikibase\ApiSetSiteLink'] 			= $dir . 'includes/api/ApiSetSiteLink.php';

// includes/content
$wgAutoloadClasses['Wikibase\EntityContent'] 			= $dir . 'includes/content/EntityContent.php';
$wgAutoloadClasses['Wikibase\EntityContentFactory'] 	= $dir . 'includes/content/EntityContentFactory.php';
$wgAutoloadClasses['Wikibase\EntityHandler'] 			= $dir . 'includes/content/EntityHandler.php';
$wgAutoloadClasses['Wikibase\ItemContent'] 				= $dir . 'includes/content/ItemContent.php';
$wgAutoloadClasses['Wikibase\ItemHandler'] 				= $dir . 'includes/content/ItemHandler.php';
$wgAutoloadClasses['Wikibase\PropertyContent'] 			= $dir . 'includes/content/PropertyContent.php';
$wgAutoloadClasses['Wikibase\PropertyHandler'] 			= $dir . 'includes/content/PropertyHandler.php';
$wgAutoloadClasses['Wikibase\QueryContent'] 			= $dir . 'includes/content/QueryContent.php';
$wgAutoloadClasses['Wikibase\QueryHandler'] 			= $dir . 'includes/content/QueryHandler.php';

// includes/specials
$wgAutoloadClasses['SpecialCreateEntity'] 				= $dir . 'includes/specials/SpecialCreateEntity.php';
$wgAutoloadClasses['SpecialCreateItem'] 				= $dir . 'includes/specials/SpecialCreateItem.php';
$wgAutoloadClasses['SpecialItemByTitle'] 				= $dir . 'includes/specials/SpecialItemByTitle.php';
$wgAutoloadClasses['SpecialItemResolver'] 				= $dir . 'includes/specials/SpecialItemResolver.php';
$wgAutoloadClasses['SpecialItemDisambiguation'] 		= $dir . 'includes/specials/SpecialItemDisambiguation.php';
$wgAutoloadClasses['SpecialWikibasePage'] 				= $dir . 'includes/specials/SpecialWikibasePage.php';
$wgAutoloadClasses['SpecialSetLabel'] 					= $dir . 'includes/specials/SpecialSetLabel.php';
$wgAutoloadClasses['SpecialWikibaseQueryPage']			= $dir . 'includes/specials/SpecialWikibaseQueryPage.php';
$wgAutoloadClasses['SpecialEntitiesWithoutLabel'] 	    = $dir . 'includes/specials/SpecialEntitiesWithoutLabel.php';

// includes/store
$wgAutoloadClasses['Wikibase\IdGenerator'] 				= $dir . 'includes/store/IdGenerator.php';
$wgAutoloadClasses['Wikibase\Store'] 					= $dir . 'includes/store/Store.php';
$wgAutoloadClasses['Wikibase\StoreFactory'] 			= $dir . 'includes/store/StoreFactory.php';
$wgAutoloadClasses['Wikibase\TermCache'] 				= $dir . 'includes/store/TermCache.php';
$wgAutoloadClasses['Wikibase\TermMatchScoreCalculator'] = $dir . 'includes/store/TermMatchScoreCalculator.php';
$wgAutoloadClasses['Wikibase\EntityPerPage']			= $dir . 'includes/store/EntityPerPage.php';

// includes/store/sql
$wgAutoloadClasses['Wikibase\SqlIdGenerator'] 			= $dir . 'includes/store/sql/SqlIdGenerator.php';
$wgAutoloadClasses['Wikibase\SqlStore'] 				= $dir . 'includes/store/sql/SqlStore.php';
$wgAutoloadClasses['Wikibase\TermSqlCache'] 			= $dir . 'includes/store/sql/TermSqlCache.php';
$wgAutoloadClasses['Wikibase\EntityPerPageTable']		= $dir . 'includes/store/sql/EntityPerPageTable.php';

// includes/updates
$wgAutoloadClasses['Wikibase\EntityDeletionUpdate'] 	= $dir . 'includes/updates/EntityDeletionUpdate.php';
$wgAutoloadClasses['Wikibase\EntityModificationUpdate'] = $dir . 'includes/updates/EntityModificationUpdate.php';
$wgAutoloadClasses['Wikibase\ItemDeletionUpdate'] 		= $dir . 'includes/updates/ItemDeletionUpdate.php';
$wgAutoloadClasses['Wikibase\ItemModificationUpdate'] 	= $dir . 'includes/updates/ItemModificationUpdate.php';

// maintenance
$wgAutoloadClasses['Wikibase\RebuildTermsSearchKey'] 	= $dir . 'maintenance/rebuildTermsSearchKey.php';
$wgAutoloadClasses['Wikibase\RebuildEntityPerPage'] 	= $dir . 'maintenance/rebuildEntityPerPage.php';

// tests
$wgAutoloadClasses['Wikibase\Test\TestItemContents'] 		= $dir . 'tests/phpunit/TestItemContents.php';
$wgAutoloadClasses['Wikibase\Test\ActionTestCase'] 			= $dir . 'tests/phpunit/includes/actions/ActionTestCase.php';
$wgAutoloadClasses['Wikibase\Test\ApiModifyItemBase'] 		= $dir . 'tests/phpunit/includes/api/ApiModifyItemBase.php';
$wgAutoloadClasses['Wikibase\Test\ApiLangAttributeBase'] 	= $dir . 'tests/phpunit/includes/api/ApiLangAttributeBase.php';
$wgAutoloadClasses['Wikibase\Test\EntityContentTest'] 		= $dir . 'tests/phpunit/includes/content/EntityContentTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityHandlerTest'] 		= $dir . 'tests/phpunit/includes/content/EntityHandlerTest.php';
$wgAutoloadClasses['Wikibase\Test\SpecialPageTestBase'] 	= $dir . 'tests/phpunit/includes/specials/SpecialPageTestBase.php';

// API module registration
$wgAPIModules['wbgetentities'] 						= 'Wikibase\ApiGetEntities';
$wgAPIModules['wbsetlabel'] 						= 'Wikibase\ApiSetLabel';
$wgAPIModules['wbsetdescription'] 					= 'Wikibase\ApiSetDescription';
$wgAPIModules['wbsearchentities'] 					= 'Wikibase\ApiSearchEntities';
$wgAPIModules['wbsetaliases'] 						= 'Wikibase\ApiSetAliases';
$wgAPIModules['wbeditentity'] 						= 'Wikibase\ApiEditEntity';
$wgAPIModules['wblinktitles'] 						= 'Wikibase\ApiLinkTitles';
$wgAPIModules['wbsetsitelink'] 						= 'Wikibase\ApiSetSiteLink';


// Special page registration
$wgSpecialPages['CreateItem'] 						= 'SpecialCreateItem';
$wgSpecialPages['ItemByTitle'] 						= 'SpecialItemByTitle';
$wgSpecialPages['ItemDisambiguation'] 				= 'SpecialItemDisambiguation';
$wgSpecialPages['SetLabel'] 						= 'SpecialSetLabel';
$wgSpecialPages['EntitiesWithoutLabel'] 			= 'SpecialEntitiesWithoutLabel';


// Hooks
$wgHooks['WikibaseDefaultSettings'][] 			    = 'Wikibase\RepoHooks::onWikibaseDefaultSettings';
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'Wikibase\RepoHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][] 						= 'Wikibase\RepoHooks::registerUnitTests';
$wgHooks['NamespaceIsMovable'][]					= 'Wikibase\RepoHooks::onNamespaceIsMovable';
$wgHooks['NewRevisionFromEditComplete'][]			= 'Wikibase\RepoHooks::onNewRevisionFromEditComplete';
$wgHooks['SkinTemplateNavigation'][] 				= 'Wikibase\RepoHooks::onPageTabs';
$wgHooks['RecentChange_save'][]						= 'Wikibase\RepoHooks::onRecentChangeSave';
$wgHooks['ArticleDeleteComplete'][] 				= 'Wikibase\RepoHooks::onArticleDeleteComplete';
$wgHooks['ArticleUndelete'][]						= 'Wikibase\RepoHooks::onArticleUndelete';
$wgHooks['LinkBegin'][] 							= 'Wikibase\RepoHooks::onLinkBegin';
$wgHooks['OutputPageBodyAttributes'][] 				= 'Wikibase\RepoHooks::onOutputPageBodyAttributes';
//FIXME: handle other types of entities with autocomments too!
$wgHooks['FormatAutocomments'][]					= array( 'Wikibase\Autocomment::onFormat', array( CONTENT_MODEL_WIKIBASE_ITEM, "wikibase-item" ) );
$wgHooks['FormatAutocomments'][]					= array( 'Wikibase\Autocomment::onFormat', array( CONTENT_MODEL_WIKIBASE_PROPERTY, "wikibase-property" ) );
$wgHooks['FormatAutocomments'][]					= array( 'Wikibase\Autocomment::onFormat', array( CONTENT_MODEL_WIKIBASE_QUERY, "wikibase-query" ) );
$wgHooks['PageHistoryLineEnding'][]					= 'Wikibase\RepoHooks::onPageHistoryLineEnding';
$wgHooks['WikibaseRebuildData'][] 					= 'Wikibase\RepoHooks::onWikibaseRebuildData';
$wgHooks['WikibaseDeleteData'][] 					= 'Wikibase\RepoHooks::onWikibaseDeleteData';
$wgHooks['ApiCheckCanExecute'][] 					= 'Wikibase\RepoHooks::onApiCheckCanExecute';
$wgHooks['SetupAfterCache'][] 						= 'Wikibase\RepoHooks::onSetupAfterCache';
$wgHooks['ShowSearchHit'][] 						= 'Wikibase\RepoHooks::onShowSearchHit';

// Resource Loader Modules:
$wgResourceModules = array_merge( $wgResourceModules, include( "$dir/resources/Resources.php" ) );

// register hooks and handlers
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_ITEM] = '\Wikibase\ItemHandler';
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_PROPERTY] = '\Wikibase\PropertyHandler';
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_QUERY] = '\Wikibase\QueryHandler';

$wgWBStores = array();
$wgWBStores['sqlstore'] = 'Wikibase\SqlStore';

if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
	include_once( $dir . 'Wikibase.experimental.php' );
}

unset( $dir );
