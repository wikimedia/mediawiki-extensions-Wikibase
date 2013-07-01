<?php

/**
 * Class registration file for the WikibaseLib component.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
return call_user_func( function() {

	$classes = array(
		// Autoloading
		'Wikibase\LibHooks' => 'WikibaseLib.hooks.php',

		// includes
		'Wikibase\Arrayalizer' => 'includes/Arrayalizer.php',
		'Wikibase\ChangeNotifier' => 'includes/ChangeNotifier.php',
		'Wikibase\ChangeNotificationJob' => 'includes/ChangeNotificationJob.php',
		'Wikibase\UpdateRepoOnMoveJob' => 'includes/UpdateRepoOnMoveJob.php',
		'Wikibase\ChangesTable' => 'includes/ChangesTable.php',
		'Wikibase\DiffOpValueFormatter' => 'includes/DiffOpValueFormatter.php',
		'Wikibase\DiffView' => 'includes/DiffView.php',
		'Wikibase\LanguageWithConversion' => 'includes/LanguageWithConversion.php',
		'Wikibase\Lib\GuidGenerator' => 'includes/GuidGenerator.php',
		'Wikibase\Lib\V4GuidGenerator' => 'includes/GuidGenerator.php',
		'Wikibase\Lib\EntityRetrievingDataTypeLookup' => 'includes/EntityRetrievingDataTypeLookup.php',
		'Wikibase\Lib\PropertyInfoDataTypeLookup' => 'includes/PropertyInfoDataTypeLookup.php',
		'Wikibase\Lib\ClaimGuidGenerator' => 'includes/GuidGenerator.php',
		'Wikibase\Lib\ClaimGuidValidator' => 'includes/ClaimGuidValidator.php',
		'Wikibase\Lib\InMemoryDataTypeLookup' => 'includes/InMemoryDataTypeLookup.php',
		'Wikibase\LibRegistry' => 'includes/LibRegistry.php',
		'Wikibase\Template' => 'includes/TemplateRegistry.php',
		'Wikibase\TemplateRegistry' => 'includes/TemplateRegistry.php',
		'Wikibase\ReferencedEntitiesFinder' => 'includes/ReferencedEntitiesFinder.php',
		'Wikibase\Lib\PropertyDataTypeLookup' => 'includes/PropertyDataTypeLookup.php',
		'Wikibase\Lib\PropertyNotFoundException' => 'includes/PropertyNotFoundException.php',
		'Wikibase\Settings' => 'includes/Settings.php',
		'Wikibase\SettingsArray' => 'includes/SettingsArray.php',
		'Wikibase\SiteLink' => 'includes/SiteLink.php',
		'Wikibase\Lib\SnakConstructionService' => 'includes/SnakConstructionService.php',
		'Wikibase\Lib\SnakFormatter' => 'includes/SnakFormatter.php',
		'Wikibase\Term' => 'includes/Term.php',
		'Wikibase\Lib\TermsToClaimsTranslator' => 'includes/TermsToClaimsTranslator.php',
		'Wikibase\Lib\TypedValueFormatter' => 'includes/TypedValueFormatter.php',
		'Wikibase\Utils' => 'includes/Utils.php',
		'Wikibase\WikibaseDiffOpFactory' => 'includes/WikibaseDiffOpFactory.php',
		'Wikibase\Lib\WikibaseDataTypeBuilders' => 'includes/WikibaseDataTypeBuilders.php',

		// includes/changes
		'Wikibase\Change' => 'includes/changes/Change.php',
		'Wikibase\ChangeRow' => 'includes/changes/ChangeRow.php',
		'Wikibase\DiffChange' => 'includes/changes/DiffChange.php',
		'Wikibase\EntityChange' => 'includes/changes/EntityChange.php',
		'Wikibase\ItemChange' => 'includes/changes/ItemChange.php',

		'Wikibase\ClaimDiffer' => 'includes/ClaimDiffer.php',
		'Wikibase\ClaimDifference' => 'includes/ClaimDifference.php',
		'Wikibase\ClaimDifferenceVisualizer' => 'includes/ClaimDifferenceVisualizer.php',


		'Wikibase\EntityDiffVisualizer' => 'includes/EntityDiffVisualizer.php',
		'Wikibase\EntityFactory' => 'includes/EntityFactory.php',

		// includes/formatters
		'Wikibase\Lib\EntityIdFormatter' => 'includes/formatters/EntityIdFormatter.php',
		'Wikibase\Lib\EntityIdLabelFormatter' => 'includes/formatters/EntityIdLabelFormatter.php',

		// includes/modules
		'Wikibase\RepoAccessModule' => 'includes/modules/RepoAccessModule.php',
		'Wikibase\SitesModule' => 'includes/modules/SitesModule.php',
		'Wikibase\TemplateModule' => 'includes/modules/TemplateModule.php',

		// includes/parsers
		'Wikibase\Lib\EntityIdParser' => 'includes/parsers/EntityIdParser.php',


		// includes/specials
		'SpecialWikibasePage' => 'includes/specials/SpecialWikibasePage.php',
		'SpecialWikibaseQueryPage' => 'includes/specials/SpecialWikibaseQueryPage.php',

		// includes/api/serializers
		'Wikibase\Lib\Serializers\ByPropertyListSerializer' => 'includes/serializers/ByPropertyListSerializer.php',
		'Wikibase\Lib\Serializers\ByPropertyListUnserializer' => 'includes/serializers/ByPropertyListUnserializer.php',
		'Wikibase\Lib\Serializers\ClaimSerializer' => 'includes/serializers/ClaimSerializer.php',
		'Wikibase\Lib\Serializers\ClaimsSerializer' => 'includes/serializers/ClaimsSerializer.php',
		'Wikibase\Lib\Serializers\EntitySerializer' => 'includes/serializers/EntitySerializer.php',
		'Wikibase\Lib\Serializers\ItemSerializer' => 'includes/serializers/ItemSerializer.php',
		'Wikibase\Lib\Serializers\LabelSerializer' => 'includes/serializers/LabelSerializer.php',
		'Wikibase\Lib\Serializers\DescriptionSerializer' => 'includes/serializers/DescriptionSerializer.php',
		'Wikibase\Lib\Serializers\AliasSerializer' => 'includes/serializers/AliasSerializer.php',
		'Wikibase\Lib\Serializers\PropertySerializer' => 'includes/serializers/PropertySerializer.php',
		'Wikibase\Lib\Serializers\ReferenceSerializer' => 'includes/serializers/ReferenceSerializer.php',
		'Wikibase\Lib\Serializers\SerializationOptions' => 'includes/serializers/SerializationOptions.php',
		'Wikibase\Lib\Serializers\MultiLangSerializationOptions' => 'includes/serializers/SerializationOptions.php',
		'Wikibase\Lib\Serializers\EntitySerializationOptions' => 'includes/serializers/SerializationOptions.php',
		'Wikibase\Lib\Serializers\Serializer' => 'includes/serializers/Serializer.php',
		'Wikibase\Lib\Serializers\SerializerFactory' => 'includes/serializers/SerializerFactory.php',
		'Wikibase\Lib\Serializers\SerializerObject' => 'includes/serializers/SerializerObject.php',
		'Wikibase\Lib\Serializers\SnakSerializer' => 'includes/serializers/SnakSerializer.php',
		'Wikibase\Lib\Serializers\Unserializer' => 'includes/serializers/Unserializer.php',

		// includes/store
		'Wikibase\ChunkCache' => 'includes/store/ChunkCache.php',
		'Wikibase\ChunkAccess' => 'includes/store/ChunkAccess.php',
		'Wikibase\EntityLookup' => 'includes/store/EntityLookup.php',
		'Wikibase\EntityRevision' => 'includes/store/EntityRevision.php',
		'Wikibase\EntityRevisionLookup' => 'includes/store/EntityRevisionLookup.php',
		'Wikibase\PropertyLabelResolver' => 'includes/store/PropertyLabelResolver.php',
		'Wikibase\EntityUsageIndex' => 'includes/store/EntityUsageIndex.php',
		'Wikibase\SiteLinkCache' => 'includes/store/SiteLinkCache.php',
		'Wikibase\SiteLinkLookup' => 'includes/store/SiteLinkLookup.php',
		'Wikibase\StorageException' => 'includes/store/StorageException.php',
		'Wikibase\TermIndex' => 'includes/store/TermIndex.php',
		'Wikibase\TermCombinationMatchFinder' => 'includes/store/TermCombinationMatchFinder.php',
		'Wikibase\TermMatchScoreCalculator' => 'includes/store/TermMatchScoreCalculator.php',
		'Wikibase\TermPropertyLabelResolver' => 'includes/store/TermPropertyLabelResolver.php',

		'Wikibase\PropertyInfoStore' => 'includes/store/PropertyInfoStore.php',
		'Wikibase\DummyPropertyInfoStore' => 'includes/store/DummyPropertyInfoStore.php',
		'Wikibase\CachingPropertyInfoStore' => 'includes/store/CachingPropertyInfoStore.php',

		// includes/store/sql
		'Wikibase\CachingEntityLoader' => 'includes/store/sql/CachingEntityLoader.php',
		'Wikibase\SiteLinkTable' => 'includes/store/sql/SiteLinkTable.php',
		'Wikibase\WikiPageEntityLookup' => 'includes/store/sql/WikiPageEntityLookup.php',
		'Wikibase\TermSqlIndex' => 'includes/store/sql/TermSqlIndex.php',
		'Wikibase\PropertyInfoTable' => 'includes/store/sql/PropertyInfoTable.php',

		// includes/util
		'Wikibase\HttpAcceptNegotiator' => 'includes/util/HttpAcceptNegotiator.php',
		'Wikibase\HttpAcceptParser' => 'includes/util/HttpAcceptParser.php',

		// includes/Validators
		'Wikibase\Validators\CompositeValidator' => 'includes/Validators/CompositeValidator.php',
		'Wikibase\Validators\DataFieldValidator' => 'includes/Validators/DataFieldValidator.php',
		'Wikibase\Validators\DataValueValidator' => 'includes/Validators/DataValueValidator.php',
		'Wikibase\Validators\EntityExistsValidator' => 'includes/Validators/EntityExistsValidator.php',
		'Wikibase\Validators\EntityIdValidator' => 'includes/Validators/EntityIdValidator.php',
		'Wikibase\Validators\RegexValidator' => 'includes/Validators/RegexValidator.php',
		'Wikibase\Validators\StringLengthValidator' => 'includes/Validators/StringLengthValidator.php',
		'Wikibase\Validators\TypeValidator' => 'includes/Validators/TypeValidator.php',
		'Wikibase\Validators\ValidatorErrorLocalizer' => 'includes/Validators/ValidatorErrorLocalizer.php',

		// tests
		'Wikibase\Test\SpecialPageTestBase' => 'tests/phpunit/specials/SpecialPageTestBase.php',
		'Wikibase\Test\TemplateTest' => 'tests/phpunit/TemplateTest.php',
		'Wikibase\Test\TemplateRegistryTest' => 'tests/phpunit/TemplateRegistryTest.php',
		'Wikibase\Test\ChangeRowTest' => 'tests/phpunit/changes/ChangeRowTest.php',
		'Wikibase\Test\DiffChangeTest' => 'tests/phpunit/changes/DiffChangeTest.php',
		'Wikibase\Test\EntityChangeTest' => 'tests/phpunit/changes/EntityChangeTest.php',
		'Wikibase\Test\TestChanges' => 'tests/phpunit/changes/TestChanges.php',

		'Wikibase\Test\EntityRefreshTest' => 'tests/phpunit/changes/EntityRefreshTest.php',
		'Wikibase\Test\SerializerBaseTest' => 'tests/phpunit/serializers/SerializerBaseTest.php',
		'Wikibase\Test\EntitySerializerBaseTest' => 'tests/phpunit/serializers/EntitySerializerBaseTest.php',
		'Wikibase\Test\EntityTestCase' => 'tests/phpunit/entity/EntityTestCase.php',
		'Wikibase\Lib\Test\Serializers\UnserializerBaseTest' => 'tests/phpunit/serializers/UnserializerBaseTest.php',
		'Wikibase\Test\MockPropertyLabelResolver' => 'tests/phpunit/MockPropertyLabelResolver.php',
		'Wikibase\Test\PropertyLabelResolverTest' => 'tests/phpunit/store/PropertyLabelResolverTest.php',
		'Wikibase\Test\MockRepository' => 'tests/phpunit/MockRepository.php',
		'Wikibase\Test\EntityLookupTest' => 'tests/phpunit/EntityLookupTest.php',
		'Wikibase\Test\MockChunkAccess' => 'tests/phpunit/store/MockChunkAccess.php',
		'Wikibase\Test\TermIndexTest' => 'tests/phpunit/store/TermIndexTest.php',
		'Wikibase\Test\MockPropertyInfoStore' => 'tests/phpunit/store/MockPropertyInfoStore.php',

		'Wikibase\Test\PropertyInfoStoreTestHelper' => 'tests/phpunit/store/PropertyInfoStoreTestHelper.php',
	);

	return $classes;

} );
