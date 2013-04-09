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

define( 'WB_VERSION', '0.4 alpha'
	. ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ? '/experimental' : '' ) );

$wgExtensionCredits['wikibase'][] = array(
	'path' => __DIR__,
	'name' => 'Wikibase Repository',
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

$dir = __DIR__ . '/';

// rights
// names should be according to other naming scheme
$wgGroupPermissions['*']['item-override']		= true;
$wgGroupPermissions['*']['item-create']			= true;
$wgGroupPermissions['*']['item-remove']			= true;
$wgGroupPermissions['*']['property-override']	= true;
$wgGroupPermissions['*']['property-create']		= true;
$wgGroupPermissions['*']['property-remove']		= true;
$wgGroupPermissions['*']['alias-update']		= true;
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
$wgAutoloadClasses['Wikibase\Autocomment']							= $dir . 'includes/Autocomment.php';
$wgAutoloadClasses['Wikibase\ClaimSaver']							= $dir . 'includes/ClaimSaver.php';
$wgAutoloadClasses['Wikibase\DataTypeSelector']						= $dir . 'includes/DataTypeSelector.php';
$wgAutoloadClasses['Wikibase\Repo\DBConnectionProvider']			= $dir . 'includes/DBConnectionProvider.php';
$wgAutoloadClasses['Wikibase\EditEntity'] 							= $dir . 'includes/EditEntity.php';
$wgAutoloadClasses['Wikibase\EntityContentDiffView'] 				= $dir . 'includes/EntityContentDiffView.php';
$wgAutoloadClasses['Wikibase\ItemContentDiffView'] 					= $dir . 'includes/ItemContentDiffView.php';
$wgAutoloadClasses['Wikibase\ItemDisambiguation'] 					= $dir . 'includes/ItemDisambiguation.php';
$wgAutoloadClasses['Wikibase\EntityView']							= $dir . 'includes/EntityView.php';
$wgAutoloadClasses['Wikibase\ExceptionWithCode']					= $dir . 'includes/ExceptionWithCode.php';
$wgAutoloadClasses['Wikibase\ItemView'] 							= $dir . 'includes/ItemView.php';
$wgAutoloadClasses['Wikibase\LabelDescriptionDuplicateDetector'] 	= $dir . 'includes/LabelDescriptionDuplicateDetector.php';
$wgAutoloadClasses['Wikibase\Repo\LazyDBConnectionProvider']		= $dir . 'includes/LazyDBConnectionProvider.php';
$wgAutoloadClasses['Wikibase\MultiLangConstraintDetector'] 			= $dir . 'includes/MultiLangConstraintDetector.php';
$wgAutoloadClasses['Wikibase\NamespaceUtils']						= $dir . 'includes/NamespaceUtils.php';
$wgAutoloadClasses['Wikibase\PropertyView']							= $dir . 'includes/PropertyView.php';
$wgAutoloadClasses['Wikibase\Summary']								= $dir . 'includes/Summary.php';
$wgAutoloadClasses['Wikibase\ClaimSummaryBuilder']					= $dir . 'includes/ClaimSummaryBuilder.php';
$wgAutoloadClasses['Wikibase\Repo\WikibaseRepo']					= $dir . 'includes/WikibaseRepo.php';

// includes/actions
$wgAutoloadClasses['Wikibase\HistoryEntityAction'] 		= $dir . 'includes/actions/HistoryEntityAction.php';
$wgAutoloadClasses['Wikibase\HistoryItemAction'] 		= $dir . 'includes/actions/HistoryItemAction.php';
$wgAutoloadClasses['Wikibase\HistoryPropertyAction'] 	= $dir . 'includes/actions/HistoryPropertyAction.php';
$wgAutoloadClasses['Wikibase\EditEntityAction'] 		= $dir . 'includes/actions/EditEntityAction.php';
$wgAutoloadClasses['Wikibase\EditItemAction'] 			= $dir . 'includes/actions/EditItemAction.php';
$wgAutoloadClasses['Wikibase\EditPropertyAction'] 		= $dir . 'includes/actions/EditPropertyAction.php';
$wgAutoloadClasses['Wikibase\ViewEntityAction'] 		= $dir . 'includes/actions/ViewEntityAction.php';
$wgAutoloadClasses['Wikibase\ViewItemAction'] 			= $dir . 'includes/actions/ViewItemAction.php';
$wgAutoloadClasses['Wikibase\ViewPropertyAction'] 		= $dir . 'includes/actions/ViewPropertyAction.php';
$wgAutoloadClasses['Wikibase\SubmitEntityAction'] 		= $dir . 'includes/actions/EditEntityAction.php';
$wgAutoloadClasses['Wikibase\SubmitItemAction'] 		= $dir . 'includes/actions/EditItemAction.php';
$wgAutoloadClasses['Wikibase\SubmitPropertyAction'] 	= $dir . 'includes/actions/EditPropertyAction.php';

// includes/api
$wgAutoloadClasses['Wikibase\Api\ApiWikibase'] 			= $dir . 'includes/api/ApiWikibase.php';
$wgAutoloadClasses['Wikibase\Api\IAutocomment'] 		= $dir . 'includes/api/IAutocomment.php';
$wgAutoloadClasses['Wikibase\Api\EditEntity'] 			= $dir . 'includes/api/EditEntity.php';
$wgAutoloadClasses['Wikibase\Api\GetEntities'] 			= $dir . 'includes/api/GetEntities.php';
$wgAutoloadClasses['Wikibase\Api\LinkTitles'] 			= $dir . 'includes/api/LinkTitles.php';
$wgAutoloadClasses['Wikibase\Api\ModifyClaim']			= $dir . 'includes/api/ModifyClaim.php';
$wgAutoloadClasses['Wikibase\Api\ModifyEntity'] 		= $dir . 'includes/api/ModifyEntity.php';
$wgAutoloadClasses['Wikibase\Api\ModifyLangAttribute'] 	= $dir . 'includes/api/ModifyLangAttribute.php';
$wgAutoloadClasses['Wikibase\Api\SearchEntities'] 		= $dir . 'includes/api/SearchEntities.php';
$wgAutoloadClasses['Wikibase\Api\SetAliases'] 			= $dir . 'includes/api/SetAliases.php';
$wgAutoloadClasses['Wikibase\Api\SetDescription'] 		= $dir . 'includes/api/SetDescription.php';
$wgAutoloadClasses['Wikibase\Api\SetLabel'] 			= $dir . 'includes/api/SetLabel.php';
$wgAutoloadClasses['Wikibase\Api\SetSiteLink'] 			= $dir . 'includes/api/SetSiteLink.php';
$wgAutoloadClasses['Wikibase\Api\CreateClaim'] 			= $dir . 'includes/api/CreateClaim.php';
$wgAutoloadClasses['Wikibase\Api\GetClaims'] 			= $dir . 'includes/api/GetClaims.php';
$wgAutoloadClasses['Wikibase\Api\RemoveClaims'] 		= $dir . 'includes/api/RemoveClaims.php';
$wgAutoloadClasses['Wikibase\Api\SetClaimValue'] 		= $dir . 'includes/api/SetClaimValue.php';
$wgAutoloadClasses['Wikibase\Api\SetReference'] 		= $dir . 'includes/api/SetReference.php';
$wgAutoloadClasses['Wikibase\Api\RemoveReferences'] 	= $dir . 'includes/api/RemoveReferences.php';
$wgAutoloadClasses['Wikibase\Api\SetClaim']				= $dir . 'includes/api/SetClaim.php';

// includes/content
$wgAutoloadClasses['Wikibase\EntityContent'] 			= $dir . 'includes/content/EntityContent.php';
$wgAutoloadClasses['Wikibase\EntityContentFactory'] 	= $dir . 'includes/content/EntityContentFactory.php';
$wgAutoloadClasses['Wikibase\EntityHandler'] 			= $dir . 'includes/content/EntityHandler.php';
$wgAutoloadClasses['Wikibase\ItemContent'] 				= $dir . 'includes/content/ItemContent.php';
$wgAutoloadClasses['Wikibase\ItemHandler'] 				= $dir . 'includes/content/ItemHandler.php';
$wgAutoloadClasses['Wikibase\LinkedDataSerializer'] 	= $dir . 'includes/content/LinkedDataSerializer.php';
$wgAutoloadClasses['Wikibase\PropertyContent'] 			= $dir . 'includes/content/PropertyContent.php';
$wgAutoloadClasses['Wikibase\PropertyHandler'] 			= $dir . 'includes/content/PropertyHandler.php';

// includes/specials
$wgAutoloadClasses['SpecialNewEntity'] 					= $dir . 'includes/specials/SpecialNewEntity.php';
$wgAutoloadClasses['SpecialNewItem'] 					= $dir . 'includes/specials/SpecialNewItem.php';
$wgAutoloadClasses['SpecialNewProperty'] 				= $dir . 'includes/specials/SpecialNewProperty.php';
$wgAutoloadClasses['SpecialItemByTitle'] 				= $dir . 'includes/specials/SpecialItemByTitle.php';
$wgAutoloadClasses['SpecialItemResolver'] 				= $dir . 'includes/specials/SpecialItemResolver.php';
$wgAutoloadClasses['SpecialItemDisambiguation'] 		= $dir . 'includes/specials/SpecialItemDisambiguation.php';
$wgAutoloadClasses['SpecialSetEntity'] 					= $dir . 'includes/specials/SpecialSetEntity.php';
$wgAutoloadClasses['SpecialSetLabel'] 					= $dir . 'includes/specials/SpecialSetLabel.php';
$wgAutoloadClasses['SpecialSetDescription'] 			= $dir . 'includes/specials/SpecialSetDescription.php';
$wgAutoloadClasses['SpecialSetAliases'] 				= $dir . 'includes/specials/SpecialSetAliases.php';
$wgAutoloadClasses['SpecialEntitiesWithoutLabel'] 	    = $dir . 'includes/specials/SpecialEntitiesWithoutLabel.php';
$wgAutoloadClasses['SpecialItemsWithoutSitelinks'] 	    = $dir . 'includes/specials/SpecialItemsWithoutSitelinks.php';
$wgAutoloadClasses['SpecialListDatatypes'] 				= $dir . 'includes/specials/SpecialListDatatypes.php';
$wgAutoloadClasses['SpecialDispatchStats'] 				= $dir . 'includes/specials/SpecialDispatchStats.php';

// includes/store
$wgAutoloadClasses['Wikibase\EntityPerPage']			= $dir . 'includes/store/EntityPerPage.php';
$wgAutoloadClasses['Wikibase\IdGenerator'] 				= $dir . 'includes/store/IdGenerator.php';
$wgAutoloadClasses['Wikibase\Store'] 					= $dir . 'includes/store/Store.php';
$wgAutoloadClasses['Wikibase\StoreFactory'] 			= $dir . 'includes/store/StoreFactory.php';

// includes/store/sql
$wgAutoloadClasses['Wikibase\SqlIdGenerator'] 			= $dir . 'includes/store/sql/SqlIdGenerator.php';
$wgAutoloadClasses['Wikibase\SqlStore'] 				= $dir . 'includes/store/sql/SqlStore.php';
$wgAutoloadClasses['Wikibase\EntityPerPageTable']		= $dir . 'includes/store/sql/EntityPerPageTable.php';
$wgAutoloadClasses['Wikibase\DispatchStats']			= $dir . 'includes/store/sql/DispatchStats.php';
$wgAutoloadClasses['Wikibase\TermSearchKeyBuilder'] 	= $dir . 'includes/store/sql/TermSearchKeyBuilder.php';

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
$wgAutoloadClasses['Wikibase\Test\Api\ModifyItemBase'] 		= $dir . 'tests/phpunit/includes/api/ModifyItemBase.php';
$wgAutoloadClasses['Wikibase\Test\Api\LangAttributeBase'] 	= $dir . 'tests/phpunit/includes/api/LangAttributeBase.php';
$wgAutoloadClasses['Wikibase\Test\EntityContentTest'] 		= $dir . 'tests/phpunit/includes/content/EntityContentTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityHandlerTest'] 		= $dir . 'tests/phpunit/includes/content/EntityHandlerTest.php';
$wgAutoloadClasses['Wikibase\Test\TermIndexTest'] 			= $dir . 'tests/phpunit/includes/store/TermIndexTest.php';

if ( !class_exists( 'MessageReporter' ) ) {
	$wgAutoloadClasses['MessageReporter'] 			= $dir . 'includes/MessageReporter.php';
	$wgAutoloadClasses['ObservableMessageReporter'] = $dir . 'includes/MessageReporter.php';
	$wgAutoloadClasses['NullMessageReporter'] 		= $dir . 'includes/MessageReporter.php';
}

// EasyRdf
$wgAutoloadClasses['EasyRdf_Exception'] 				= $dir . 'includes/content/easyRdf/EasyRdf/Exception.php';
$wgAutoloadClasses['EasyRdf_Format'] 					= $dir . 'includes/content/easyRdf/EasyRdf/Format.php';
$wgAutoloadClasses['EasyRdf_Graph'] 					= $dir . 'includes/content/easyRdf/EasyRdf/Graph.php';
$wgAutoloadClasses['EasyRdf_Namespace'] 				= $dir . 'includes/content/easyRdf/EasyRdf/Namespace.php';
$wgAutoloadClasses['EasyRdf_Literal'] 					= $dir . 'includes/content/easyRdf/EasyRdf/Literal.php';
$wgAutoloadClasses['EasyRdf_Literal_Boolean'] 			= $dir . 'includes/content/easyRdf/EasyRdf/Literal/Boolean.php';
$wgAutoloadClasses['EasyRdf_Literal_Date'] 				= $dir . 'includes/content/easyRdf/EasyRdf/Literal/Date.php';
$wgAutoloadClasses['EasyRdf_Literal_DateTime'] 			= $dir . 'includes/content/easyRdf/EasyRdf/Literal/DateTime.php';
$wgAutoloadClasses['EasyRdf_Literal_Decimal'] 			= $dir . 'includes/content/easyRdf/EasyRdf/Literal/Decimal.php';
$wgAutoloadClasses['EasyRdf_Literal_HexBinary'] 		= $dir . 'includes/content/easyRdf/EasyRdf/Literal/HexBinary.php';
$wgAutoloadClasses['EasyRdf_Literal_Integer'] 			= $dir . 'includes/content/easyRdf/EasyRdf/Literal/Integer.php';
$wgAutoloadClasses['EasyRdf_Resource'] 					= $dir . 'includes/content/easyRdf/EasyRdf/Resource.php';
$wgAutoloadClasses['EasyRdf_Serialiser'] 				= $dir . 'includes/content/easyRdf/EasyRdf/Serialiser.php';
$wgAutoloadClasses['EasyRdf_Serialiser_GraphViz'] 		= $dir . 'includes/content/easyRdf/EasyRdf/Serialiser/GraphViz.php';
$wgAutoloadClasses['EasyRdf_Serialiser_RdfPhp'] 		= $dir . 'includes/content/easyRdf/EasyRdf/Serialiser/RdfPhp.php';
$wgAutoloadClasses['EasyRdf_Serialiser_Ntriples'] 		= $dir . 'includes/content/easyRdf/EasyRdf/Serialiser/Ntriples.php';
$wgAutoloadClasses['EasyRdf_Serialiser_Json'] 			= $dir . 'includes/content/easyRdf/EasyRdf/Serialiser/Json.php';
$wgAutoloadClasses['EasyRdf_Serialiser_RdfXml'] 		= $dir . 'includes/content/easyRdf/EasyRdf/Serialiser/RdfXml.php';
$wgAutoloadClasses['EasyRdf_Serialiser_Turtle'] 		= $dir . 'includes/content/easyRdf/EasyRdf/Serialiser/Turtle.php';
$wgAutoloadClasses['EasyRdf_TypeMapper'] 				= $dir . 'includes/content/easyRdf/EasyRdf/TypeMapper.php';
$wgAutoloadClasses['EasyRdf_Utils'] 					= $dir . 'includes/content/easyRdf/EasyRdf/Utils.php';

// API module registration
$wgAPIModules['wbgetentities'] 						= 'Wikibase\Api\GetEntities';
$wgAPIModules['wbsetlabel'] 						= 'Wikibase\Api\SetLabel';
$wgAPIModules['wbsetdescription'] 					= 'Wikibase\Api\SetDescription';
$wgAPIModules['wbsearchentities'] 					= 'Wikibase\Api\SearchEntities';
$wgAPIModules['wbsetaliases'] 						= 'Wikibase\Api\SetAliases';
$wgAPIModules['wbeditentity'] 						= 'Wikibase\Api\EditEntity';
$wgAPIModules['wblinktitles'] 						= 'Wikibase\Api\LinkTitles';
$wgAPIModules['wbsetsitelink'] 						= 'Wikibase\Api\SetSiteLink';
$wgAPIModules['wbcreateclaim'] 						= 'Wikibase\Api\CreateClaim';
$wgAPIModules['wbgetclaims'] 						= 'Wikibase\Api\GetClaims';
$wgAPIModules['wbremoveclaims'] 					= 'Wikibase\Api\RemoveClaims';
$wgAPIModules['wbsetclaimvalue'] 					= 'Wikibase\Api\SetClaimValue';
$wgAPIModules['wbsetreference'] 					= 'Wikibase\Api\SetReference';
$wgAPIModules['wbremovereferences'] 				= 'Wikibase\Api\RemoveReferences';
$wgAPIModules['wbsetclaim'] 						= 'Wikibase\Api\SetClaim';

// Special page registration
$wgSpecialPages['NewItem'] 							= 'SpecialNewItem';
$wgSpecialPages['NewProperty'] 						= 'SpecialNewProperty';
$wgSpecialPages['ItemByTitle'] 						= 'SpecialItemByTitle';
$wgSpecialPages['ItemDisambiguation'] 				= 'SpecialItemDisambiguation';
$wgSpecialPages['ItemsWithoutSitelinks']			= 'SpecialItemsWithoutSitelinks';
$wgSpecialPages['SetLabel'] 						= 'SpecialSetLabel';
$wgSpecialPages['SetDescription'] 					= 'SpecialSetDescription';
$wgSpecialPages['SetAliases'] 						= 'SpecialSetAliases';
$wgSpecialPages['EntitiesWithoutLabel'] 			= 'SpecialEntitiesWithoutLabel';
$wgSpecialPages['NewProperty'] 						= 'SpecialNewProperty';
$wgSpecialPages['ListDatatypes']					= 'SpecialListDatatypes';
$wgSpecialPages['DispatchStats']					= 'SpecialDispatchStats';

// Special page groups
$wgSpecialPageGroups['NewItem']						= 'wikibaserepo';
$wgSpecialPageGroups['NewProperty']					= 'wikibaserepo';
$wgSpecialPageGroups['ItemByTitle']					= 'wikibaserepo';
$wgSpecialPageGroups['ItemDisambiguation']			= 'wikibaserepo';
$wgSpecialPageGroups['ItemsWithoutSitelinks']		= 'wikibaserepo';
$wgSpecialPageGroups['SetLabel']					= 'wikibaserepo';
$wgSpecialPageGroups['SetDescription']				= 'wikibaserepo';
$wgSpecialPageGroups['SetAliases']					= 'wikibaserepo';
$wgSpecialPageGroups['EntitiesWithoutLabel']		= 'wikibaserepo';
$wgSpecialPageGroups['EntityData']					= 'wikibaserepo';
$wgSpecialPageGroups['ListDatatypes']				= 'wikibaserepo';
$wgSpecialPageGroups['DispatchStats']				= 'wikibaserepo';

// Hooks
$wgHooks['BeforePageDisplay'][]						= 'Wikibase\RepoHooks::onBeforePageDisplay';
$wgHooks['WikibaseDefaultSettings'][] 				= 'Wikibase\RepoHooks::onWikibaseDefaultSettings';
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
$wgHooks['PageHistoryLineEnding'][]					= 'Wikibase\RepoHooks::onPageHistoryLineEnding';
$wgHooks['WikibaseRebuildData'][] 					= 'Wikibase\RepoHooks::onWikibaseRebuildData';
$wgHooks['WikibaseDeleteData'][] 					= 'Wikibase\RepoHooks::onWikibaseDeleteData';
$wgHooks['ApiCheckCanExecute'][] 					= 'Wikibase\RepoHooks::onApiCheckCanExecute';
$wgHooks['SetupAfterCache'][] 						= 'Wikibase\RepoHooks::onSetupAfterCache';
$wgHooks['ShowSearchHit'][] 						= 'Wikibase\RepoHooks::onShowSearchHit';
$wgHooks['TitleGetRestrictionTypes'][]				= 'Wikibase\RepoHooks::onTitleGetRestrictionTypes';
$wgHooks['AbuseFilter-contentToString'][]			= 'Wikibase\RepoHooks::onAbuseFilterContentToString';
$wgHooks['SpecialPage_reorderPages'][]				= 'Wikibase\RepoHooks::onSpecialPage_reorderPages';

// Resource Loader Modules:
$wgResourceModules = array_merge( $wgResourceModules, include( "$dir/resources/Resources.php" ) );

// register hooks and handlers
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_ITEM] 		= '\Wikibase\ItemHandler';
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_PROPERTY] 	= '\Wikibase\PropertyHandler';

$wgWBStores = array();

$wgWBStores['sqlstore'] = 'Wikibase\SqlStore';

unset( $dir );

include_once( __DIR__ . '/config/Wikibase.default.php' );

if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
	include_once( __DIR__ . '/config/Wikibase.experimental.php' );
}

unset( $dir );
