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
		'Wikibase\EditEntity' => 'includes/EditEntity.php',
		'Wikibase\EntityContentDiffView' => 'includes/EntityContentDiffView.php',
		'Wikibase\ItemContentDiffView' => 'includes/ItemContentDiffView.php',
		'Wikibase\ItemDisambiguation' => 'includes/ItemDisambiguation.php',
		'Wikibase\EntityView' => 'includes/EntityView.php',
		'Wikibase\ExceptionWithCode' => 'includes/ExceptionWithCode.php',
		'Wikibase\ItemView' => 'includes/ItemView.php',
		'Wikibase\LabelDescriptionDuplicateDetector' => 'includes/LabelDescriptionDuplicateDetector.php',
		'Wikibase\MultiLangConstraintDetector' => 'includes/MultiLangConstraintDetector.php',
		'Wikibase\NamespaceUtils' => 'includes/NamespaceUtils.php',
		'Wikibase\PropertyView' => 'includes/PropertyView.php',
		'Wikibase\Summary' => 'includes/Summary.php',
		'Wikibase\Repo\WikibaseRepo' => 'includes/WikibaseRepo.php',

		// includes/changeop
		'Wikibase\ChangeOps' => 'includes/changeop/ChangeOps.php',
		'Wikibase\ChangeOp' => 'includes/changeop/ChangeOp.php',
		'Wikibase\ChangeOpLabel' => 'includes/changeop/ChangeOpLabel.php',
		'Wikibase\ChangeOpDescription' => 'includes/changeop/ChangeOpDescription.php',
		'Wikibase\ChangeOpAliases' => 'includes/changeop/ChangeOpAliases.php',
		'Wikibase\ChangeOpSiteLink' => 'includes/changeop/ChangeOpSiteLink.php',

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
		'Wikibase\Api\ItemByTitleHelper' => 'includes/api/ItemByTitleHelper.php',
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
		'Wikibase\Api\RemoveQualifiers' => 'includes/api/RemoveQualifiers.php',
		'Wikibase\Api\SetQualifier' => 'includes/api/SetQualifier.php',
		'Wikibase\Api\SnakValidationHelper' => 'includes/api/SnakValidationHelper.php',

		// includes/content
		'Wikibase\EntityContent' => 'includes/content/EntityContent.php',
		'Wikibase\EntityContentFactory' => 'includes/content/EntityContentFactory.php',
		'Wikibase\EntityHandler' => 'includes/content/EntityHandler.php',
		'Wikibase\ItemContent' => 'includes/content/ItemContent.php',
		'Wikibase\ItemHandler' => 'includes/content/ItemHandler.php',
		'Wikibase\PropertyContent' => 'includes/content/PropertyContent.php',
		'Wikibase\PropertyHandler' => 'includes/content/PropertyHandler.php',
		'Wikibase\RdfBuilder' => 'includes/rdf/RdfBuilder.php',
		'Wikibase\RdfSerializer' => 'includes/rdf/RdfSerializer.php',

		// includes/specials
		'SpecialNewEntity' => 'includes/specials/SpecialNewEntity.php',
		'SpecialNewItem' => 'includes/specials/SpecialNewItem.php',
		'SpecialNewProperty' => 'includes/specials/SpecialNewProperty.php',
		'SpecialItemByTitle' => 'includes/specials/SpecialItemByTitle.php',
		'SpecialItemResolver' => 'includes/specials/SpecialItemResolver.php',
		'SpecialItemDisambiguation' => 'includes/specials/SpecialItemDisambiguation.php',
		'SpecialModifyEntity' => 'includes/specials/SpecialModifyEntity.php',
		'SpecialSetEntity' => 'includes/specials/SpecialSetEntity.php',
		'SpecialSetLabel' => 'includes/specials/SpecialSetLabel.php',
		'SpecialSetDescription' => 'includes/specials/SpecialSetDescription.php',
		'SpecialSetAliases' => 'includes/specials/SpecialSetAliases.php',
		'SpecialSetSiteLink' => 'includes/specials/SpecialSetSiteLink.php',
		'SpecialEntitiesWithoutLabel' => 'includes/specials/SpecialEntitiesWithoutLabel.php',
		'SpecialItemsWithoutSitelinks' => 'includes/specials/SpecialItemsWithoutSitelinks.php',
		'SpecialListDatatypes' => 'includes/specials/SpecialListDatatypes.php',
		'SpecialDispatchStats' => 'includes/specials/SpecialDispatchStats.php',
		'SpecialEntityData' => 'includes/specials/SpecialEntityData.php',
		'Wikibase\LinkedData\EntityDataSerializationService' => 'includes/LinkedData/EntityDataSerializationService.php',
		'Wikibase\LinkedData\EntityDataRequestHandler' => 'includes/LinkedData/EntityDataRequestHandler.php',
		'Wikibase\LinkedData\EntityDataUriManager' => 'includes/LinkedData/EntityDataUriManager.php',

		// includes/store
		'Wikibase\EntityPerPage' => 'includes/store/EntityPerPage.php',
		'Wikibase\IdGenerator' => 'includes/store/IdGenerator.php',
		'Wikibase\Store' => 'includes/store/Store.php',
		'Wikibase\StoreFactory' => 'includes/store/StoreFactory.php',

		// includes/store/sql
		'Wikibase\SqlIdGenerator' => 'includes/store/sql/SqlIdGenerator.php',
		'Wikibase\SqlStore' => 'includes/store/sql/SqlStore.php',
		'Wikibase\EntityPerPageBuilder' => 'includes/store/sql/EntityPerPageBuilder.php',
		'Wikibase\EntityPerPageTable' => 'includes/store/sql/EntityPerPageTable.php',
		'Wikibase\DispatchStats' => 'includes/store/sql/DispatchStats.php',
		'Wikibase\TermSearchKeyBuilder' => 'includes/store/sql/TermSearchKeyBuilder.php',

		// includes/updates
		'Wikibase\EntityDeletionUpdate' => 'includes/updates/EntityDeletionUpdate.php',
		'Wikibase\EntityModificationUpdate' => 'includes/updates/EntityModificationUpdate.php',
		'Wikibase\ItemDeletionUpdate' => 'includes/updates/ItemDeletionUpdate.php',
		'Wikibase\ItemModificationUpdate' => 'includes/updates/ItemModificationUpdate.php',

		// includes/Validators
		'Wikibase\Validators\SnakValidator' => 'includes/Validators/SnakValidator.php',

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
		'Wikibase\Test\RdfBuilderTest' => 'tests/phpunit/includes/rdf/RdfBuilderTest.php',

		'MessageReporter' => 'includes/MessageReporter.php',
		'ObservableMessageReporter' => 'includes/MessageReporter.php',
		'NullMessageReporter' => 'includes/MessageReporter.php',

		'Wikibase\Test\EntityDataSerializationServiceTest' => 'tests/phpunit/includes/LinkedData/EntityDataSerializationServiceTest.php',
		'Wikibase\Test\EntityDataRequestHandlerTest' => 'tests/phpunit/includes/LinkedData/EntityDataRequestHandlerTest.php',
		'Wikibase\Test\EntityDataTestProvider' => 'tests/phpunit/includes/LinkedData/EntityDataTestProvider.php',
		'Wikibase\Test\TestValidator' => 'tests/phpunit/includes/Validators/TestValidator.php',
	);


	// EasyRdf
	if ( file_exists( __DIR__ . '/../contrib/easyRdf/EasyRdf' ) ) {
		$rdfClasses = array(
			'EasyRdf_Exception' => '../contrib/easyRdf/EasyRdf/Exception.php',
			'EasyRdf_Format' => '../contrib/easyRdf/EasyRdf/Format.php',
			'EasyRdf_Graph' => '../contrib/easyRdf/EasyRdf/Graph.php',
			'EasyRdf_Namespace' => '../contrib/easyRdf/EasyRdf/Namespace.php',
			'EasyRdf_Literal' => '../contrib/easyRdf/EasyRdf/Literal.php',
			'EasyRdf_Literal_Boolean' => '../contrib/easyRdf/EasyRdf/Literal/Boolean.php',
			'EasyRdf_Literal_Date' => '../contrib/easyRdf/EasyRdf/Literal/Date.php',
			'EasyRdf_Literal_DateTime' => '../contrib/easyRdf/EasyRdf/Literal/DateTime.php',
			'EasyRdf_Literal_Decimal' => '../contrib/easyRdf/EasyRdf/Literal/Decimal.php',
			'EasyRdf_Literal_HexBinary' => '../contrib/easyRdf/EasyRdf/Literal/HexBinary.php',
			'EasyRdf_Literal_Integer' => '../contrib/easyRdf/EasyRdf/Literal/Integer.php',
			'EasyRdf_Resource' => '../contrib/easyRdf/EasyRdf/Resource.php',
			'EasyRdf_Serialiser' => '../contrib/easyRdf/EasyRdf/Serialiser.php',
			'EasyRdf_Serialiser_GraphViz' => '../contrib/easyRdf/EasyRdf/Serialiser/GraphViz.php',
			'EasyRdf_Serialiser_RdfPhp' => '../contrib/easyRdf/EasyRdf/Serialiser/RdfPhp.php',
			'EasyRdf_Serialiser_Ntriples' => '../contrib/easyRdf/EasyRdf/Serialiser/Ntriples.php',
			'EasyRdf_Serialiser_Json' => '../contrib/easyRdf/EasyRdf/Serialiser/Json.php',
			'EasyRdf_Serialiser_RdfXml' => '../contrib/easyRdf/EasyRdf/Serialiser/RdfXml.php',
			'EasyRdf_Serialiser_Turtle' => '../contrib/easyRdf/EasyRdf/Serialiser/Turtle.php',
			'EasyRdf_TypeMapper' => '../contrib/easyRdf/EasyRdf/TypeMapper.php',
			'EasyRdf_Utils' => '../contrib/easyRdf/EasyRdf/Utils.php',
		);

		$classes = array_merge( $classes, $rdfClasses );
	}

	return $classes;

} );
