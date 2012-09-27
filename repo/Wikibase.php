<?php

/**
 * Initialization file for the Wikibase extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:Wikibase
 * Support					https://www.mediawiki.org/wiki/Extension_talk:Wikibase
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikidataRepo.git
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 * @author Daniel Kinzler
 */

/**
 * This documentation group collects source code files belonging to Wikibase.
 *
 * @defgroup Wikibase Wikibase
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

if ( !defined( 'WBL_VERSION' ) ) { // No version constant to check against :/
	die( '<b>Error:</b> Wikibase depends on the <a href="https://www.mediawiki.org/wiki/Extension:WikibaseLib">WikibaseLib</a> extension.' );
}

// TODO: enable
//if ( !array_key_exists( 'CountryNames', $wgAutoloadClasses ) ) { // No version constant to check against :/
//	die( '<b>Error:</b> Wikibase depends on the <a href="https://www.mediawiki.org/wiki/Extension:CLDR">CLDR</a> extension.' );
//}

define( 'WB_VERSION', '0.2 alpha' );

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

$dir = __DIR__ . '/';

// rights
// names should be according to other naming scheme
$wgGroupPermissions['*']['item-override']		= true;
$wgGroupPermissions['*']['item-create']			= true;
$wgGroupPermissions['*']['item-remove']			= true;
$wgGroupPermissions['*']['alias-add']			= true;
$wgGroupPermissions['*']['alias-set']			= true;
$wgGroupPermissions['*']['alias-remove']		= true;
$wgGroupPermissions['*']['sitelink-remove']		= true;
$wgGroupPermissions['*']['sitelink-update']		= true;
$wgGroupPermissions['*']['linktitles-update']	= true;
$wgGroupPermissions['*']['label-remove']		= true;
$wgGroupPermissions['*']['label-update']		= true;
$wgGroupPermissions['*']['description-remove']	= true;
$wgGroupPermissions['*']['description-update']	= true;

// i18n
$wgExtensionMessagesFiles['Wikibase'] 		= $dir . 'Wikibase.i18n.php';
$wgExtensionMessagesFiles['WikibaseAlias'] 	= $dir . 'Wikibase.i18n.alias.php';
$wgExtensionMessagesFiles['WikibaseNS'] 	= $dir . 'Wikibase.i18n.namespaces.php';


// Autoloading
$wgAutoloadClasses['Wikibase\RepoHooks'] 				= $dir . 'Wikibase.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\EntityContentDiffView'] 	= $dir . 'includes/EntityContentDiffView.php';
$wgAutoloadClasses['Wikibase\ItemContentDiffView'] 		= $dir . 'includes/ItemContentDiffView.php';
$wgAutoloadClasses['Wikibase\ItemDisambiguation'] 		= $dir . 'includes/ItemDisambiguation.php';
$wgAutoloadClasses['Wikibase\EntityView']				= $dir . 'includes/EntityView.php';
$wgAutoloadClasses['Wikibase\ItemView'] 				= $dir . 'includes/ItemView.php';
$wgAutoloadClasses['Wikibase\PropertyView']				= $dir . 'includes/PropertyView.php';
$wgAutoloadClasses['Wikibase\Autocomment'] 				= $dir . 'includes/Autocomment.php';
$wgAutoloadClasses['Wikibase\EditEntity'] 				= $dir . 'includes/EditEntity.php';

// includes/actions
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
$wgAutoloadClasses['Wikibase\ApiGetItems'] 				= $dir . 'includes/api/ApiGetItems.php';
$wgAutoloadClasses['Wikibase\ApiModifyEntity'] 			= $dir . 'includes/api/ApiModifyEntity.php';
$wgAutoloadClasses['Wikibase\ApiModifyLangAttribute'] 	= $dir . 'includes/api/ApiModifyLangAttribute.php';
$wgAutoloadClasses['Wikibase\ApiSetLabel'] 				= $dir . 'includes/api/ApiSetLabel.php';
$wgAutoloadClasses['Wikibase\ApiSetDescription'] 		= $dir . 'includes/api/ApiSetDescription.php';
$wgAutoloadClasses['Wikibase\ApiSetSiteLink'] 			= $dir . 'includes/api/ApiSetSiteLink.php';
$wgAutoloadClasses['Wikibase\ApiSetAliases'] 			= $dir . 'includes/api/ApiSetAliases.php';
$wgAutoloadClasses['Wikibase\ApiSetItem'] 				= $dir . 'includes/api/ApiSetItem.php';
$wgAutoloadClasses['Wikibase\ApiLinkTitles'] 			= $dir . 'includes/api/ApiLinkTitles.php';

// includes/content
$wgAutoloadClasses['Wikibase\EntityContent'] 			= $dir . 'includes/content/EntityContent.php';
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
$wgAutoloadClasses['SpecialCreateProperty'] 			= $dir . 'includes/specials/SpecialCreateProperty.php';
$wgAutoloadClasses['SpecialItemByTitle'] 				= $dir . 'includes/specials/SpecialItemByTitle.php';
$wgAutoloadClasses['SpecialListDatatypes'] 				= $dir . 'includes/specials/SpecialListDatatypes.php';
$wgAutoloadClasses['SpecialItemResolver'] 				= $dir . 'includes/specials/SpecialItemResolver.php';
$wgAutoloadClasses['SpecialItemDisambiguation'] 		= $dir . 'includes/specials/SpecialItemDisambiguation.php';
$wgAutoloadClasses['SpecialWikibasePage'] 				= $dir . 'includes/specials/SpecialWikibasePage.php';

// includes/store
$wgAutoloadClasses['Wikibase\IdGenerator'] 				= $dir . 'includes/store/IdGenerator.php';
$wgAutoloadClasses['Wikibase\Store'] 					= $dir . 'includes/store/Store.php';
$wgAutoloadClasses['Wikibase\StoreFactory'] 			= $dir . 'includes/store/StoreFactory.php';
$wgAutoloadClasses['Wikibase\TermCache'] 				= $dir . 'includes/store/TermCache.php';


// includes/store/sql
$wgAutoloadClasses['Wikibase\SqlIdGenerator'] 			= $dir . 'includes/store/sql/SqlIdGenerator.php';
$wgAutoloadClasses['Wikibase\SqlStore'] 				= $dir . 'includes/store/sql/SqlStore.php';
$wgAutoloadClasses['Wikibase\TermSqlCache'] 			= $dir . 'includes/store/sql/TermSqlCache.php';

// includes/updates
$wgAutoloadClasses['Wikibase\EntityDeletionUpdate'] 	= $dir . 'includes/updates/EntityDeletionUpdate.php';
$wgAutoloadClasses['Wikibase\EntityModificationUpdate'] = $dir . 'includes/updates/EntityModificationUpdate.php';
$wgAutoloadClasses['Wikibase\ItemDeletionUpdate'] 		= $dir . 'includes/updates/ItemDeletionUpdate.php';
$wgAutoloadClasses['Wikibase\ItemModificationUpdate'] 	= $dir . 'includes/updates/ItemModificationUpdate.php';

// tests
$wgAutoloadClasses['Wikibase\Test\TestItemContents'] 		= $dir . 'tests/phpunit/TestItemContents.php';
$wgAutoloadClasses['Wikibase\Test\ApiModifyItemBase'] 		= $dir . 'tests/phpunit/includes/api/ApiModifyItemBase.php';
$wgAutoloadClasses['Wikibase\Test\ApiLangAttributeBase'] 	= $dir . 'tests/phpunit/includes/api/ApiLangAttributeBase.php';
$wgAutoloadClasses['Wikibase\Test\EntityContentTest'] 		= $dir . 'tests/phpunit/includes/content/EntityContentTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityHandlerTest'] 		= $dir . 'tests/phpunit/includes/content/EntityHandlerTest.php';
$wgAutoloadClasses['Wikibase\Test\SpecialPageTestBase'] 	= $dir . 'tests/phpunit/includes/specials/SpecialPageTestBase.php';
$wgAutoloadClasses['Wikibase\Test\ActionTestCase'] 			= $dir . 'tests/phpunit/includes/actions/ActionTestCase.php';
$wgAutoloadClasses['Wikibase\Test\EntityHandlerTest'] 			= $dir . 'tests/phpunit/includes/content/EntityHandlerTest.php';

// API module registration
$wgAPIModules['wbgetitems'] 						= 'Wikibase\ApiGetItems';
$wgAPIModules['wbsetlabel'] 						= 'Wikibase\ApiSetLabel';
$wgAPIModules['wbsetdescription'] 					= 'Wikibase\ApiSetDescription';
$wgAPIModules['wbsetsitelink'] 						= 'Wikibase\ApiSetSiteLink';
$wgAPIModules['wbsetaliases'] 						= 'Wikibase\ApiSetAliases';
$wgAPIModules['wbsetitem'] 							= 'Wikibase\ApiSetItem';
$wgAPIModules['wblinktitles'] 						= 'Wikibase\ApiLinkTitles';


// Special page registration
$wgSpecialPages['CreateItem'] 						= 'SpecialCreateItem';
$wgSpecialPages['CreateProperty'] 					= 'SpecialCreateProperty';
$wgSpecialPages['ItemByTitle'] 						= 'SpecialItemByTitle';
$wgSpecialPages['ItemDisambiguation'] 				= 'SpecialItemDisambiguation';
$wgSpecialPages['ListDatatypes']        			= 'SpecialListDatatypes';



// Hooks
$wgHooks['WikibaseDefaultSettings'][] 			    = 'Wikibase\RepoHooks::onWikibaseDefaultSettings';
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'Wikibase\RepoHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][] 						= 'Wikibase\RepoHooks::registerUnitTests';
$wgHooks['PageContentLanguage'][]					= 'Wikibase\RepoHooks::onPageContentLanguage';
$wgHooks['ResourceLoaderTestModules'][]				= 'Wikibase\RepoHooks::onResourceLoaderTestModules';
$wgHooks['NamespaceIsMovable'][]					= 'Wikibase\RepoHooks::onNamespaceIsMovable';
$wgHooks['NewRevisionFromEditComplete'][]			= 'Wikibase\RepoHooks::onNewRevisionFromEditComplete';
$wgHooks['SkinTemplateNavigation'][] 				= 'Wikibase\RepoHooks::onPageTabs';
$wgHooks['ArticleDeleteComplete'][] 				= 'Wikibase\RepoHooks::onArticleDeleteComplete';
$wgHooks['LinkBegin'][] 							= 'Wikibase\RepoHooks::onLinkBegin';
$wgHooks['OutputPageBodyAttributes'][] 				= 'Wikibase\RepoHooks::onOutputPageBodyAttributes';
$wgHooks['FormatAutocomments'][]					= array( 'Wikibase\Autocomment::onFormat', array( CONTENT_MODEL_WIKIBASE_ITEM, "wikibase-item" ) );
$wgHooks['PageHistoryLineEnding'][]					= 'Wikibase\RepoHooks::onPageHistoryLineEnding';
$wgHooks['WikibaseRebuildData'][] 					= 'Wikibase\RepoHooks::onWikibaseRebuildData';
$wgHooks['WikibaseDeleteData'][] 					= 'Wikibase\RepoHooks::onWikibaseDeleteData';
$wgHooks['ApiCheckCanExecute'][] 					= 'Wikibase\RepoHooks::onApiCheckCanExecute';
$wgHooks['SetupAfterCache'][] 						= 'Wikibase\RepoHooks::onSetupAfterCache';

// Resource Loader Modules:
$wgResourceModules = array_merge( $wgResourceModules, include( "$dir/resources/Resources.php" ) );

// register hooks and handlers
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_ITEM] = '\Wikibase\ItemHandler';
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_PROPERTY] = '\Wikibase\PropertyHandler';
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_QUERY] = '\Wikibase\QueryHandler';

$wgWBStores = array();
$wgWBStores['sqlstore'] = 'Wikibase\SqlStore';

unset( $dir );
