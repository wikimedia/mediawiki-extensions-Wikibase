<?php

/**
 * Class registration file for the WikibaseRepo extension.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
return call_user_func( function() {

	$classes = array(
		'Wikibase\RepoHooks' => 'Wikibase.hooks.php',

		// includes
		'Wikibase\Autocomment' => 'includes/Autocomment.php',
		'Wikibase\ClaimSaver' => 'includes/ClaimSaver.php',
		'Wikibase\ClaimSummaryBuilder' => 'includes/ClaimSummaryBuilder.php',
		'Wikibase\DataTypeSelector' => 'includes/DataTypeSelector.php',
		'Wikibase\Repo\DBConnectionProvider' => 'includes/DBConnectionProvider.php',
		'Wikibase\EditEntity' => 'includes/EditEntity.php',
		'Wikibase\EntityContentDiffView' => 'includes/EntityContentDiffView.php',
		'Wikibase\ItemContentDiffView' => 'includes/ItemContentDiffView.php',
		'Wikibase\ItemDisambiguation' => 'includes/ItemDisambiguation.php',
		'Wikibase\EntityView' => 'includes/EntityView.php',
		'Wikibase\ExceptionWithCode' => 'includes/ExceptionWithCode.php',
		'Wikibase\ItemView' => 'includes/ItemView.php',
		'Wikibase\LabelDescriptionDuplicateDetector' => 'includes/LabelDescriptionDuplicateDetector.php',
		'Wikibase\Repo\LazyDBConnectionProvider' => 'includes/LazyDBConnectionProvider.php',
		'Wikibase\MultiLangConstraintDetector' => 'includes/MultiLangConstraintDetector.php',
		'Wikibase\NamespaceUtils' => 'includes/NamespaceUtils.php',
		'Wikibase\PropertyView' => 'includes/PropertyView.php',
		'Wikibase\Summary' => 'includes/Summary.php',
		'Wikibase\Repo\WikibaseRepo' => 'includes/WikibaseRepo.php',

		// includes/actions
		'Wikibase\HistoryEntityAction' => 'includes/actions/HistoryEntityAction.php',
		'Wikibase\HistoryItemAction' => 'includes/actions/HistoryItemAction.php',
		'Wikibase\HistoryPropertyAction' => 'includes/actions/HistoryPropertyAction.php',
		'Wikibase\EditEntityAction' => 'includes/actions/EditEntityAction.php',
		'Wikibase\EditItemAction' => 'includes/actions/EditItemAction.php',
		'Wikibase\EditPropertyAction' => 'includes/actions/EditPropertyAction.php',
		'Wikibase\ViewEntityAction' => 'includes/actions/ViewEntityAction.php',
		'Wikibase\ViewItemAction' => 'includes/actions/ViewItemAction.php',
		'Wikibase\ViewPropertyAction' => 'includes/actions/ViewPropertyAction.php',
		'Wikibase\SubmitEntityAction' => 'includes/actions/EditEntityAction.php',
		'Wikibase\SubmitItemAction' => 'includes/actions/EditItemAction.php',
		'Wikibase\SubmitPropertyAction' => 'includes/actions/EditPropertyAction.php',

		// includes/api
		'Wikibase\Api\ApiWikibase' => 'includes/api/ApiWikibase.php',
		'Wikibase\Api\IAutocomment' => 'includes/api/IAutocomment.php',
		'Wikibase\Api\EditEntity' => 'includes/api/EditEntity.php',
		'Wikibase\Api\GetEntities' => 'includes/api/GetEntities.php',
		'Wikibase\Api\LinkTitles' => 'includes/api/LinkTitles.php',
		'Wikibase\Api\ModifyClaim' => 'includes/api/ModifyClaim.php',
		'Wikibase\Api\ModifyEntity' => 'includes/api/ModifyEntity.php',
		'Wikibase\Api\ModifyLangAttribute' => 'includes/api/ModifyLangAttribute.php',
		'Wikibase\Api\SearchEntities' => 'includes/api/SearchEntities.php',
		'Wikibase\Api\SetAliases' => 'includes/api/SetAliases.php',
		'Wikibase\Api\SetDescription' => 'includes/api/SetDescription.php',
		'Wikibase\Api\SetLabel' => 'includes/api/SetLabel.php',
		'Wikibase\Api\SetSiteLink' => 'includes/api/SetSiteLink.php',
		'Wikibase\Api\CreateClaim' => 'includes/api/CreateClaim.php',
		'Wikibase\Api\GetClaims' => 'includes/api/GetClaims.php',
		'Wikibase\Api\RemoveClaims' => 'includes/api/RemoveClaims.php',
		'Wikibase\Api\SetClaimValue' => 'includes/api/SetClaimValue.php',
		'Wikibase\Api\SetReference' => 'includes/api/SetReference.php',
		'Wikibase\Api\RemoveReferences' => 'includes/api/RemoveReferences.php',
		'Wikibase\Api\SetClaim' => 'includes/api/SetClaim.php',

		// includes/content
		'Wikibase\EntityContent' => 'includes/content/EntityContent.php',
		'Wikibase\EntityContentFactory' => 'includes/content/EntityContentFactory.php',
		'Wikibase\EntityHandler' => 'includes/content/EntityHandler.php',
		'Wikibase\ItemContent' => 'includes/content/ItemContent.php',
		'Wikibase\ItemHandler' => 'includes/content/ItemHandler.php',
		'Wikibase\PropertyContent' => 'includes/content/PropertyContent.php',
		'Wikibase\PropertyHandler' => 'includes/content/PropertyHandler.php',
		'Wikibase\RdfBuilder' => 'includes/content/RdfBuilder.php',
		'Wikibase\RdfSerializer' => 'includes/content/RdfSerializer.php',

		// EasyRdf
		'EasyRdf_Exception' => 'includes/content/easyRdf/EasyRdf/Exception.php',
		'EasyRdf_Format' => 'includes/content/easyRdf/EasyRdf/Format.php',
		'EasyRdf_Graph' => 'includes/content/easyRdf/EasyRdf/Graph.php',
		'EasyRdf_Namespace' => 'includes/content/easyRdf/EasyRdf/Namespace.php',
		'EasyRdf_Literal' => 'includes/content/easyRdf/EasyRdf/Literal.php',
		'EasyRdf_Literal_Boolean' => 'includes/content/easyRdf/EasyRdf/Literal/Boolean.php',
		'EasyRdf_Literal_Date' => 'includes/content/easyRdf/EasyRdf/Literal/Date.php',
		'EasyRdf_Literal_DateTime' => 'includes/content/easyRdf/EasyRdf/Literal/DateTime.php',
		'EasyRdf_Literal_Decimal' => 'includes/content/easyRdf/EasyRdf/Literal/Decimal.php',
		'EasyRdf_Literal_HexBinary' => 'includes/content/easyRdf/EasyRdf/Literal/HexBinary.php',
		'EasyRdf_Literal_Integer' => 'includes/content/easyRdf/EasyRdf/Literal/Integer.php',
		'EasyRdf_Resource' => 'includes/content/easyRdf/EasyRdf/Resource.php',
		'EasyRdf_Serialiser' => 'includes/content/easyRdf/EasyRdf/Serialiser.php',
		'EasyRdf_Serialiser_GraphViz' => 'includes/content/easyRdf/EasyRdf/Serialiser/GraphViz.php',
		'EasyRdf_Serialiser_RdfPhp' => 'includes/content/easyRdf/EasyRdf/Serialiser/RdfPhp.php',
		'EasyRdf_Serialiser_Ntriples' => 'includes/content/easyRdf/EasyRdf/Serialiser/Ntriples.php',
		'EasyRdf_Serialiser_Json' => 'includes/content/easyRdf/EasyRdf/Serialiser/Json.php',
		'EasyRdf_Serialiser_RdfXml' => 'includes/content/easyRdf/EasyRdf/Serialiser/RdfXml.php',
		'EasyRdf_Serialiser_Turtle' => 'includes/content/easyRdf/EasyRdf/Serialiser/Turtle.php',
		'EasyRdf_TypeMapper' => 'includes/content/easyRdf/EasyRdf/TypeMapper.php',
		'EasyRdf_Utils' => 'includes/content/easyRdf/EasyRdf/Utils.php',

		// includes/specials
		'SpecialNewEntity' => 'includes/specials/SpecialNewEntity.php',
		'SpecialNewItem' => 'includes/specials/SpecialNewItem.php',
		'SpecialNewProperty' => 'includes/specials/SpecialNewProperty.php',
		'SpecialItemByTitle' => 'includes/specials/SpecialItemByTitle.php',
		'SpecialItemResolver' => 'includes/specials/SpecialItemResolver.php',
		'SpecialItemDisambiguation' => 'includes/specials/SpecialItemDisambiguation.php',
		'SpecialSetEntity' => 'includes/specials/SpecialSetEntity.php',
		'SpecialSetLabel' => 'includes/specials/SpecialSetLabel.php',
		'SpecialSetDescription' => 'includes/specials/SpecialSetDescription.php',
		'SpecialSetAliases' => 'includes/specials/SpecialSetAliases.php',
		'SpecialEntitiesWithoutLabel' => 'includes/specials/SpecialEntitiesWithoutLabel.php',
		'SpecialItemsWithoutSitelinks' => 'includes/specials/SpecialItemsWithoutSitelinks.php',
		'SpecialListDatatypes' => 'includes/specials/SpecialListDatatypes.php',
		'SpecialDispatchStats' => 'includes/specials/SpecialDispatchStats.php',

		// includes/store
		'Wikibase\EntityPerPage' => 'includes/store/EntityPerPage.php',
		'Wikibase\IdGenerator' => 'includes/store/IdGenerator.php',
		'Wikibase\Store' => 'includes/store/Store.php',
		'Wikibase\StoreFactory' => 'includes/store/StoreFactory.php',

		// includes/store/sql
		'Wikibase\SqlIdGenerator' => 'includes/store/sql/SqlIdGenerator.php',
		'Wikibase\SqlStore' => 'includes/store/sql/SqlStore.php',
		'Wikibase\EntityPerPageTable' => 'includes/store/sql/EntityPerPageTable.php',
		'Wikibase\DispatchStats' => 'includes/store/sql/DispatchStats.php',
		'Wikibase\TermSearchKeyBuilder' => 'includes/store/sql/TermSearchKeyBuilder.php',

		// includes/updates
		'Wikibase\EntityDeletionUpdate' => 'includes/updates/EntityDeletionUpdate.php',
		'Wikibase\EntityModificationUpdate' => 'includes/updates/EntityModificationUpdate.php',
		'Wikibase\ItemDeletionUpdate' => 'includes/updates/ItemDeletionUpdate.php',
		'Wikibase\ItemModificationUpdate' => 'includes/updates/ItemModificationUpdate.php',

		// maintenance
		'Wikibase\RebuildTermsSearchKey' => 'maintenance/rebuildTermsSearchKey.php',
		'Wikibase\RebuildEntityPerPage' => 'maintenance/rebuildEntityPerPage.php',

		// tests
		'Wikibase\Test\TestItemContents' => 'tests/phpunit/TestItemContents.php',
		'Wikibase\Test\ActionTestCase' => 'tests/phpunit/includes/actions/ActionTestCase.php',
		'Wikibase\Test\Api\ModifyItemBase' => 'tests/phpunit/includes/api/ModifyItemBase.php',
		'Wikibase\Test\Api\LangAttributeBase' => 'tests/phpunit/includes/api/LangAttributeBase.php',
		'Wikibase\Test\EntityContentTest' => 'tests/phpunit/includes/content/EntityContentTest.php',
		'Wikibase\Test\EntityHandlerTest' => 'tests/phpunit/includes/content/EntityHandlerTest.php',
		'Wikibase\Test\RdfBuilderTest' => 'tests/phpunit/includes/content/RdfBuilderTest.php',

		'MessageReporter' => 'includes/MessageReporter.php',
		'ObservableMessageReporter' => 'includes/MessageReporter.php',
		'NullMessageReporter' => 'includes/MessageReporter.php',
	);

	return $classes;

} );
