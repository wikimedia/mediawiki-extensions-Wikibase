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

		// generic things that could be factored out
		'Disposable' => 'includes/Disposable.php',

		// includes
		'Wikibase\ChangeNotifier' => 'includes/ChangeNotifier.php',
		'Wikibase\ChangeNotificationJob' => 'includes/ChangeNotificationJob.php',
		'Wikibase\UpdateRepoOnMoveJob' => 'includes/UpdateRepoOnMoveJob.php',
		'Wikibase\ChangesTable' => 'includes/ChangesTable.php',
		'Wikibase\DiffOpValueFormatter' => 'includes/DiffOpValueFormatter.php',
		'Wikibase\DiffView' => 'includes/DiffView.php',
		'Wikibase\LanguageFallbackChain' => 'includes/LanguageFallbackChain.php',
		'Wikibase\LanguageFallbackChainFactory' => 'includes/LanguageFallbackChainFactory.php',
		'Wikibase\LanguageWithConversion' => 'includes/LanguageWithConversion.php',
		'Wikibase\Lib\GuidGenerator' => 'includes/GuidGenerator.php',
		'Wikibase\Lib\V4GuidGenerator' => 'includes/V4GuidGenerator.php',
		'Wikibase\Lib\EntityRetrievingDataTypeLookup' => 'includes/EntityRetrievingDataTypeLookup.php',
		'Wikibase\Lib\PropertyInfoDataTypeLookup' => 'includes/PropertyInfoDataTypeLookup.php',
		'Wikibase\Lib\ClaimGuidGenerator' => 'includes/ClaimGuidGenerator.php',
		'Wikibase\Lib\ClaimGuidValidator' => 'includes/ClaimGuidValidator.php',
		'Wikibase\Lib\InMemoryDataTypeLookup' => 'includes/InMemoryDataTypeLookup.php',
		'Wikibase\LibRegistry' => 'includes/LibRegistry.php',
		'Wikibase\Template' => 'includes/TemplateRegistry.php',
		'Wikibase\TemplateRegistry' => 'includes/TemplateRegistry.php',
		'Wikibase\ReferencedEntitiesFinder' => 'includes/ReferencedEntitiesFinder.php',
		'Wikibase\ReferencedUrlFinder' => 'includes/ReferencedUrlFinder.php',
		'Wikibase\Lib\PropertyDataTypeLookup' => 'includes/PropertyDataTypeLookup.php',
		'Wikibase\Lib\PropertyLabelNotResolvedException' => 'includes/PropertyLabelNotResolvedException.php',
		'Wikibase\Lib\PropertyNotFoundException' => 'includes/PropertyNotFoundException.php',
		'Wikibase\Settings' => 'includes/Settings.php',
		'Wikibase\SettingsArray' => 'includes/SettingsArray.php',
		'Wikibase\SiteLink' => 'includes/SiteLink.php',
		'Wikibase\Lib\SnakConstructionService' => 'includes/SnakConstructionService.php',
		'Wikibase\SnakFactory' => 'includes/SnakFactory.php',
		'Wikibase\Summary' => 'includes/Summary.php',
		'Wikibase\Term' => 'includes/Term.php',
		'Wikibase\Lib\TermsToClaimsTranslator' => 'includes/TermsToClaimsTranslator.php',
		'Wikibase\Lib\TypedValueFormatter' => 'includes/TypedValueFormatter.php',
		'Wikibase\StringNormalizer' => 'includes/StringNormalizer.php',
		'Wikibase\Utils' => 'includes/Utils.php',
		'Wikibase\WikibaseDiffOpFactory' => 'includes/WikibaseDiffOpFactory.php',
		'Wikibase\Lib\WikibaseDataTypeBuilders' => 'includes/WikibaseDataTypeBuilders.php',
		'DataValues\DataValueFactory' => 'includes/DataValueFactory.php',

		// this should really be in core
		'MessageReporter' => 'includes/MessageReporter.php',
		'ObservableMessageReporter' => 'includes/MessageReporter.php',
		'NullMessageReporter' => 'includes/MessageReporter.php',

		// this should also really be in core
		'ExceptionHandler' => 'includes/ExceptionHandler.php',
		'ReportingExceptionHandler' => 'includes/ExceptionHandler.php',
		'RethrowingExceptionHandler' => 'includes/ExceptionHandler.php',

		// exceptions, should really really be in core
		'MessageException' => 'includes/MessageException.php',
		'UserInputException' => 'includes/UserInputException.php',

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

		// includes/Dumpers
		'Wikibase\Dumpers\JsonDumpGenerator' => 'includes/Dumpers/JsonDumpGenerator.php',

		// includes/formatters
		'Wikibase\Lib\DispatchingSnakFormatter' => 'includes/formatters/DispatchingSnakFormatter.php',
		'Wikibase\Lib\DispatchingValueFormatter' => 'includes/formatters/DispatchingValueFormatter.php',
		'Wikibase\Lib\EntityIdFormatter' => 'includes/formatters/EntityIdFormatter.php',
		'Wikibase\Lib\EntityIdLabelFormatter' => 'includes/formatters/EntityIdLabelFormatter.php',
		'Wikibase\Lib\EntityIdTitleFormatter' => 'includes/formatters/EntityIdTitleFormatter.php',
		'Wikibase\Lib\EntityIdLinkFormatter' => 'includes/formatters/EntityIdLinkFormatter.php',
		'Wikibase\Lib\MwTimeIsoFormatter' => 'includes/formatters/MwTimeIsoFormatter.php',
		'Wikibase\Lib\EscapingValueFormatter' => 'includes/formatters/EscapingValueFormatter.php',
		'Wikibase\Lib\FormattingException' => 'includes/formatters/FormattingException.php',
		'Wikibase\Lib\HtmlUrlFormatter' => 'includes/formatters/HtmlUrlFormatter.php',
		'Wikibase\Lib\MessageSnakFormatter' => 'includes/formatters/MessageSnakFormatter.php',
		'Wikibase\Lib\PropertyValueSnakFormatter' => 'includes/formatters/PropertyValueSnakFormatter.php',
		'Wikibase\Lib\SnakFormatter' => 'includes/formatters/SnakFormatter.php',
		'Wikibase\Lib\OutputFormatSnakFormatterFactory' => 'includes/formatters/OutputFormatSnakFormatterFactory.php',
		'Wikibase\Lib\OutputFormatValueFormatterFactory' => 'includes/formatters/OutputFormatValueFormatterFactory.php',
		'Wikibase\Lib\QuantityDetailsFormatter' => 'includes/formatters/QuantityDetailsFormatter.php',
		'Wikibase\Lib\UnDeserializableValueFormatter' => 'includes/formatters/UnDeserializableValueFormatter.php',
		'Wikibase\Lib\WikibaseSnakFormatterBuilders' => 'includes/formatters/WikibaseSnakFormatterBuilders.php',
		'Wikibase\Lib\WikibaseValueFormatterBuilders' => 'includes/formatters/WikibaseValueFormatterBuilders.php',
		'Wikibase\Lib\MediaWikiNumberLocalizer' => 'includes/formatters/MediaWikiNumberLocalizer.php',

		// includes/IO
		'Wikibase\IO\LineReader' => 'includes/IO/LineReader.php',
		'Wikibase\IO\EntityIdReader' => 'includes/IO/EntityIdReader.php',

		// includes/modules
		'Wikibase\RepoAccessModule' => 'includes/modules/RepoAccessModule.php',
		'Wikibase\SitesModule' => 'includes/modules/SitesModule.php',
		'Wikibase\TemplateModule' => 'includes/modules/TemplateModule.php',

		// includes/parsers
		'Wikibase\Lib\EntityIdValueParser' => 'includes/parsers/EntityIdValueParser.php',
		'Wikibase\Lib\MediaWikiNumberUnlocalizer' => 'includes/parsers/MediaWikiNumberUnlocalizer.php',

		// includes/specials
		'Wikibase\Lib\Specials\SpecialWikibasePage' => 'includes/specials/SpecialWikibasePage.php',
		'Wikibase\Lib\Specials\SpecialWikibaseQueryPage' => 'includes/specials/SpecialWikibaseQueryPage.php',

		// includes/api/serializers
		'Wikibase\Lib\Serializers\ByPropertyListSerializer' => 'includes/serializers/ByPropertyListSerializer.php',
		'Wikibase\Lib\Serializers\ByPropertyListUnserializer' => 'includes/serializers/ByPropertyListUnserializer.php',
		'Wikibase\Lib\Serializers\ClaimSerializer' => 'includes/serializers/ClaimSerializer.php',
		'Wikibase\Lib\Serializers\ClaimsSerializer' => 'includes/serializers/ClaimsSerializer.php',
		'Wikibase\Lib\Serializers\DispatchingEntitySerializer' => 'includes/serializers/DispatchingEntitySerializer.php',
		'Wikibase\Lib\Serializers\EntitySerializer' => 'includes/serializers/EntitySerializer.php',
		'Wikibase\Lib\Serializers\ItemSerializer' => 'includes/serializers/ItemSerializer.php',
		'Wikibase\Lib\Serializers\LabelSerializer' => 'includes/serializers/LabelSerializer.php',
		'Wikibase\Lib\Serializers\DescriptionSerializer' => 'includes/serializers/DescriptionSerializer.php',
		'Wikibase\Lib\Serializers\AliasSerializer' => 'includes/serializers/AliasSerializer.php',
		'Wikibase\Lib\Serializers\SiteLinkSerializer' => 'includes/serializers/SiteLinkSerializer.php',
		'Wikibase\Lib\Serializers\PropertySerializer' => 'includes/serializers/PropertySerializer.php',
		'Wikibase\Lib\Serializers\ReferenceSerializer' => 'includes/serializers/ReferenceSerializer.php',
		'Wikibase\Lib\Serializers\SerializationOptions' => 'includes/serializers/SerializationOptions.php',
		'Wikibase\Lib\Serializers\MultilingualSerializer' => 'includes/serializers/MultilingualSerializer.php',
		'Wikibase\Lib\Serializers\Serializer' => 'includes/serializers/Serializer.php',
		'Wikibase\Lib\Serializers\SerializerFactory' => 'includes/serializers/SerializerFactory.php',
		'Wikibase\Lib\Serializers\SerializerObject' => 'includes/serializers/SerializerObject.php',
		'Wikibase\Lib\Serializers\SnakSerializer' => 'includes/serializers/SnakSerializer.php',
		'Wikibase\Lib\Serializers\Unserializer' => 'includes/serializers/Unserializer.php',
		'Wikibase\Lib\Serializers\ListSerializer' => 'includes/serializers/ListSerializer.php',
		'Wikibase\Lib\Serializers\ListUnserializer' => 'includes/serializers/ListUnserializer.php',

		// includes/sites
		'SiteMatrixParser' => 'includes/sites/SiteMatrixParser.php',
		'SitesBuilder' => 'includes/sites/SitesBuilder.php',

		// includes/store
		'Wikibase\ChunkCache' => 'includes/store/ChunkCache.php',
		'Wikibase\ChunkAccess' => 'includes/store/ChunkAccess.php',
		'Wikibase\EntityInfoBuilder' => 'includes/store/EntityInfoBuilder.php',
		'Wikibase\EntityLookup' => 'includes/store/EntityLookup.php',
		'Wikibase\EntityRevision' => 'includes/store/EntityRevision.php',
		'Wikibase\EntityRevisionLookup' => 'includes/store/EntityRevisionLookup.php',
		'Wikibase\EntityTitleLookup' => 'includes/store/EntityTitleLookup.php',
		'Wikibase\PropertyLabelResolver' => 'includes/store/PropertyLabelResolver.php',
		'Wikibase\ItemUsageIndex' => 'includes/store/ItemUsageIndex.php',
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
		'Wikibase\SqlEntityInfoBuilder' => 'includes/store/sql/SqlEntityInfoBuilder.php',

		// includes/util
		'Wikibase\HttpAcceptNegotiator' => 'includes/util/HttpAcceptNegotiator.php',
		'Wikibase\HttpAcceptParser' => 'includes/util/HttpAcceptParser.php',

		// includes/Validators
		'Wikibase\Validators\CompositeValidator' => 'includes/Validators/CompositeValidator.php',
		'Wikibase\Validators\DataFieldValidator' => 'includes/Validators/DataFieldValidator.php',
		'Wikibase\Validators\DataValueValidator' => 'includes/Validators/DataValueValidator.php',
		'Wikibase\Validators\EntityExistsValidator' => 'includes/Validators/EntityExistsValidator.php',
		'Wikibase\Validators\NumberValidator' => 'includes/Validators/NumberValidator.php',
		'Wikibase\Validators\RegexValidator' => 'includes/Validators/RegexValidator.php',
		'Wikibase\Validators\StringLengthValidator' => 'includes/Validators/StringLengthValidator.php',
		'Wikibase\Validators\NumberRangeValidator' => 'includes/Validators/NumberRangeValidator.php',
		'Wikibase\Validators\TypeValidator' => 'includes/Validators/TypeValidator.php',
		'Wikibase\Validators\ValidatorErrorLocalizer' => 'includes/Validators/ValidatorErrorLocalizer.php',
		'Wikibase\Validators\UrlValidator' => 'includes/Validators/UrlValidator.php',
		'Wikibase\Validators\UrlSchemeValidators' => 'includes/Validators/UrlSchemeValidators.php',

		// tests
		'Wikibase\Test\SpecialPageTestBase' => 'tests/phpunit/specials/SpecialPageTestBase.php',
		'Wikibase\Test\TemplateTest' => 'tests/phpunit/TemplateTest.php',
		'Wikibase\Test\TemplateRegistryTest' => 'tests/phpunit/TemplateRegistryTest.php',
		'Wikibase\Test\ChangeRowTest' => 'tests/phpunit/changes/ChangeRowTest.php',
		'Wikibase\Test\DiffChangeTest' => 'tests/phpunit/changes/DiffChangeTest.php',
		'Wikibase\Test\EntityChangeTest' => 'tests/phpunit/changes/EntityChangeTest.php',
		'Wikibase\Test\TestChanges' => 'tests/phpunit/changes/TestChanges.php',

		'Wikibase\Test\SerializerBaseTest' => 'tests/phpunit/serializers/SerializerBaseTest.php',
		'Wikibase\Test\EntitySerializerBaseTest' => 'tests/phpunit/serializers/EntitySerializerBaseTest.php',
		'Wikibase\Test\EntityTestCase' => 'tests/phpunit/entity/EntityTestCase.php',
		'Wikibase\Test\MockPropertyLabelResolver' => 'tests/phpunit/MockPropertyLabelResolver.php',
		'Wikibase\Test\MockRepository' => 'tests/phpunit/MockRepository.php',
		'Wikibase\Test\EntityLookupTest' => 'tests/phpunit/EntityLookupTest.php',
		'Wikibase\Test\MockChunkAccess' => 'tests/phpunit/store/MockChunkAccess.php',
		'Wikibase\Test\TermIndexTest' => 'tests/phpunit/store/TermIndexTest.php',
		'Wikibase\Test\MockPropertyInfoStore' => 'tests/phpunit/store/MockPropertyInfoStore.php',
		'Wikibase\Test\MockSiteStore' => 'tests/phpunit/MockSiteStore.php',
		'Wikibase\Test\MockTermIndex' => 'tests/phpunit/store/MockTermIndex.php',

		'Wikibase\Test\PropertyInfoStoreTestHelper' => 'tests/phpunit/store/PropertyInfoStoreTestHelper.php',
	);

	return $classes;

} );
