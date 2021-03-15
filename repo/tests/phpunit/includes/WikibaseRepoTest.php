<?php

namespace Wikibase\Repo\Tests;

use DataValues\DataValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnboundedQuantityValue;
use DataValues\UnknownValue;
use Deserializers\Deserializer;
use LogicException;
use MediaWiki\Http\HttpRequestFactory;
use MediaWikiIntegrationTestCase;
use ReflectionClass;
use ReflectionMethod;
use RequestContext;
use User;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\DataValueFactory;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\BuilderBasedDataTypeValidatorFactory;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\PropertyInfoBuilder;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\SnakFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\ValidatorBuilders;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;

/**
 * @covers \Wikibase\Repo\WikibaseRepo
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class WikibaseRepoTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	protected function setUp(): void {
		parent::setUp();

		// WikibaseRepo service getters should never access the database or do http requests
		// https://phabricator.wikimedia.org/T243729
		$this->disallowDBAccess();
		$this->disallowHttpAccess();

		$this->settings = new SettingsArray( WikibaseRepo::getSettings()->getArrayCopy() );
		$this->entityTypeDefinitions = new EntityTypeDefinitions( [] );
		$this->entitySourceDefinitions = $this->getDefaultEntitySourceDefinitions( 'local' );
	}

	private function disallowDBAccess() {
		$this->setService(
			'DBLoadBalancerFactory',
			function() {
				$lb = $this->createMock( ILoadBalancer::class );
				$lb->expects( $this->never() )
					->method( 'getConnection' );
				$lb->expects( $this->never() )
					->method( 'getConnectionRef' );
				$lb->expects( $this->never() )
					->method( 'getMaintenanceConnectionRef' );
				$lb->method( 'getLocalDomainID' )
					->willReturn( 'banana' );

				$lbFactory = $this->createMock( LBFactory::class );
				$lbFactory->method( 'getMainLB' )
					->willReturn( $lb );

				return $lbFactory;
			}
		);
	}

	private function disallowHttpAccess() {
		$this->setService(
			'HttpRequestFactory',
			function() {
				$factory = $this->createMock( HttpRequestFactory::class );
				$factory->expects( $this->never() )
					->method( 'create' );
				$factory->expects( $this->never() )
					->method( 'request' );
				$factory->expects( $this->never() )
					->method( 'get' );
				$factory->expects( $this->never() )
					->method( 'post' );
				return $factory;
			}
		);
	}

	public function testGetDefaultValidatorBuilders() {
		$first = WikibaseRepo::getDefaultValidatorBuilders();
		$this->assertInstanceOf( ValidatorBuilders::class, $first );

		$second = WikibaseRepo::getDefaultValidatorBuilders();
		$this->assertSame( $first, $second );
	}

	public function testNewValidatorBuilders() {
		$valueToValidate = new EntityIdValue( new ItemId( 'Q123' ) );

		$repo = $this->getWikibaseRepo();

		$builders = $repo->newValidatorBuilders();
		$this->assertInstanceOf( ValidatorBuilders::class, $builders );

		// We get the resulting ValueValidators and run them against our fake remote-repo
		// custom-type EntityIdValue. We skip the existence check though, since we don't
		// have a mock lookup in place.
		$entityValidators = $builders->buildEntityValidators();
		foreach ( $entityValidators as $validator ) {
			if ( $validator instanceof EntityExistsValidator ) {
				continue;
			}

			$result = $validator->validate( $valueToValidate );
			$this->assertTrue( $result->isValid(), get_class( $validator ) );
		}
	}

	/**
	 * @dataProvider urlSchemesProvider
	 */
	public function testDefaultUrlValidators( $input, $expected ) {
		$validatorBuilders = WikibaseRepo::getDefaultValidatorBuilders();
		$urlValidator = new CompositeValidator( $validatorBuilders->buildUrlValidators() );
		$result = $urlValidator->validate( new StringValue( $input ) );
		$this->assertSame( $expected, $result->isValid() );
	}

	public function urlSchemesProvider() {
		return [
			[ 'bzr://x', true ],
			[ 'cvs://x', true ],
			[ 'ftp://x', true ],
			[ 'git://x', true ],
			[ 'http://x', true ],
			[ 'https://x', true ],
			[ 'irc://x', true ],
			[ 'mailto:x@x', true ],
			[ 'ssh://x', true ],
			[ 'svn://x', true ],

			// Supported by UrlSchemeValidators, but not enabled by default.
			[ 'ftps://x', false ],
			[ 'gopher://x', false ],
			[ 'ircs://x', false ],
			[ 'mms://x', false ],
			[ 'nntp://x', false ],
			[ 'redis://x', false ],
			[ 'sftp://x', false ],
			[ 'telnet://x', false ],
			[ 'worldwind://x', false ],
		];
	}

	public function testGetDefaultValueFormatterBuilders() {
		$first = $this->getWikibaseRepo()->getDefaultValueFormatterBuilders();
		$this->assertInstanceOf( WikibaseValueFormatterBuilders::class, $first );

		$second = $this->getWikibaseRepo()->getDefaultValueFormatterBuilders();
		$this->assertSame( $first, $second );
	}

	public function testGetDefaultSnakFormatterBuilders() {
		$first = $this->getWikibaseRepo()->getDefaultSnakFormatterBuilders();
		$this->assertInstanceOf( WikibaseSnakFormatterBuilders::class, $first );

		$second = $this->getWikibaseRepo()->getDefaultSnakFormatterBuilders();
		$this->assertSame( $first, $second );
	}

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = WikibaseRepo::getDataTypeFactory();
		$this->assertInstanceOf( DataTypeFactory::class, $returnValue );
	}

	public function testGetValueParserFactoryReturnType() {
		$returnValue = WikibaseRepo::getValueParserFactory();
		$this->assertInstanceOf( ValueParserFactory::class, $returnValue );
	}

	public function testGetEntityStoreWatcherReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityStoreWatcher();
		$this->assertInstanceOf( EntityStoreWatcher::class, $returnValue );
	}

	public function testGetEntityRevisionLookupReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityRevisionLookup();
		$this->assertInstanceOf( EntityRevisionLookup::class, $returnValue );
	}

	public function testNewRedirectCreationInteractorReturnType() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		$context = new RequestContext();
		$returnValue = $this->getWikibaseRepo()->newItemRedirectCreationInteractor( $user, $context );
		$this->assertInstanceOf( ItemRedirectCreationInteractor::class, $returnValue );
	}

	public function testNewTermSearchInteractorReturnType() {
		$returnValue = $this->getWikibaseRepo()->newTermSearchInteractor( '' );
		$this->assertInstanceOf( TermSearchInteractor::class, $returnValue );
	}

	public function testGetEntityStoreReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityStore();
		$this->assertInstanceOf( EntityStore::class, $returnValue );
	}

	public function testGetPropertyDataTypeLookupReturnType() {
		$returnValue = $this->getWikibaseRepo()->getPropertyDataTypeLookup();
		$this->assertInstanceOf( PropertyDataTypeLookup::class, $returnValue );
	}

	public function testGetEntityLookupReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityLookup();
		$this->assertInstanceOf( EntityLookup::class, $returnValue );
	}

	public function testGetSnakFactoryReturnType() {
		$returnValue = $this->getWikibaseRepo()->getSnakFactory();
		$this->assertInstanceOf( SnakFactory::class, $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = WikibaseRepo::getEntityIdParser();
		$this->assertInstanceOf( EntityIdParser::class, $returnValue );
	}

	public function testGetStatementGuidParser() {
		$returnValue = WikibaseRepo::getStatementGuidParser();
		$this->assertInstanceOf( StatementGuidParser::class, $returnValue );
	}

	public function testGetChangeOpDeserializerFactory() {
		$factory = $this->getWikibaseRepo()->getChangeOpDeserializerFactory();
		$this->assertInstanceOf( ChangeOpDeserializerFactory::class, $factory );
	}

	public function testGetLanguageFallbackLabelDescriptionLookupFactory() {
		$returnValue = $this->getWikibaseRepo()->getLanguageFallbackLabelDescriptionLookupFactory();
		$this->assertInstanceOf( LanguageFallbackLabelDescriptionLookupFactory::class, $returnValue );
	}

	public function testGetStatementGuidValidator() {
		$returnValue = WikibaseRepo::getStatementGuidValidator();
		$this->assertInstanceOf( StatementGuidValidator::class, $returnValue );
	}

	public function testGetSettingsReturnType() {
		$returnValue = WikibaseRepo::getSettings();
		$this->assertInstanceOf( SettingsArray::class, $returnValue );
	}

	public function testGetStoreReturnType() {
		$returnValue = $this->getWikibaseRepo()->getStore();
		$this->assertInstanceOf( Store::class, $returnValue );
	}

	public function testGetSnakFormatterFactory() {
		$returnValue = $this->getWikibaseRepo()->getSnakFormatterFactory();
		$this->assertInstanceOf( OutputFormatSnakFormatterFactory::class, $returnValue );
	}

	public function testGetSummaryFormatter() {
		$returnValue = $this->getWikibaseRepo()->getSummaryFormatter();
		$this->assertInstanceOf( SummaryFormatter::class, $returnValue );
	}

	public function testGetTermValidatorFactory() {
		$factory = $this->getWikibaseRepo()->getTermValidatorFactory();
		$this->assertInstanceOf( TermValidatorFactory::class, $factory );
	}

	public function testGetChangeOpFactory() {
		$returnValue = $this->getWikibaseRepo()->getChangeOpFactoryProvider();
		$this->assertInstanceOf( ChangeOpFactoryProvider::class, $returnValue );
	}

	public function testGetChangeNotifier() {
		$factory = $this->getWikibaseRepo()->getChangeNotifier();
		$this->assertInstanceOf( ChangeNotifier::class, $factory );
	}

	public function testGetContentModelMappings() {
		$array = WikibaseRepo::getContentModelMappings();
		$this->assertIsArray( $array );
		$this->assertContainsOnly( 'string', $array );
	}

	public function testGetEntityFactory() {
		$entityFactory = $this->getWikibaseRepo()->getEntityFactory();
		$this->assertInstanceOf( EntityFactory::class, $entityFactory );
	}

	private function getEntityTypeDefinitionsWithSubentities(): EntityTypeDefinitions {
		return new EntityTypeDefinitions(
			[
				'lexeme' => [
					EntityTypeDefinitions::SUB_ENTITY_TYPES => [
						'form',
					],
				],
			]
		);
	}

	public function testGetEnabledEntityTypes() {
		if ( !WikibaseSettings::isClientEnabled() ) {
			$this->markTestSkipped( 'WikibaseClient must be enabled to run this test' );
		}

		$this->entityTypeDefinitions = $this->getEntityTypeDefinitionsWithSubentities();
		$this->entitySourceDefinitions = new EntitySourceDefinitions(
			[
				new EntitySource(
					'local',
					false,
					[
						'foo' => [ 'namespaceId' => 200, 'slot' => 'main' ],
						'bar' => [ 'namespaceId' => 220, 'slot' => 'main' ],
					],
					'',
					'',
					'',
					''
				),
				new EntitySource(
					'bazwiki',
					'bazdb',
					[
						'baz' => [ 'namespaceId' => 250, 'slot' => 'main' ],
					],
					'',
					'baz',
					'baz',
					'bazwiki'
				),
				new EntitySource(
					'lexemewiki',
					'bazdb',
					[
						'lexeme' => [ 'namespaceId' => 280, 'slot' => 'main' ],
					],
					'',
					'lex',
					'lex',
					'lexwiki'
				)
			],
			$this->entityTypeDefinitions
		);

		$wikibaseRepo = $this->getWikibaseRepo();

		$enabled = $wikibaseRepo->getEnabledEntityTypes();
		$this->assertContains( 'foo', $enabled );
		$this->assertContains( 'bar', $enabled );
		$this->assertContains( 'baz', $enabled );
		$this->assertContains( 'lexeme', $enabled );
		$this->assertContains( 'form', $enabled );
	}

	private function setEntityTypeDefinitions( EntityTypeDefinitions $entityTypeDefinitions ): void {
		$this->setService(
			'WikibaseRepo.EntityTypeDefinitions',
			$entityTypeDefinitions
		);
	}

	private function setRepoSettings( SettingsArray $settings ): void {
		$this->setService( 'WikibaseRepo.Settings', $settings );
	}

	private function setEntitySourceDefinitions( EntitySourceDefinitions $entitySourceDefinitions ): void {
		$this->setService(
			'WikibaseRepo.EntitySourceDefinitions',
			$entitySourceDefinitions
		);
	}

	public function testGetExceptionLocalizer() {
		$localizer = $this->getWikibaseRepo()->getExceptionLocalizer();
		$this->assertInstanceOf( ExceptionLocalizer::class, $localizer );
	}

	public function testGetEntityContentDataCodec() {
		$codec = $this->getWikibaseRepo()->getEntityContentDataCodec();
		$this->assertInstanceOf( EntityContentDataCodec::class, $codec );
	}

	public function testGetInternalFormatEntityDeserializer() {
		$deserializer = $this->getWikibaseRepo()->getInternalFormatEntityDeserializer();
		$this->assertInstanceOf( Deserializer::class, $deserializer );
	}

	public function testGetInternalFormatStatementDeserializer() {
		$deserializer = $this->getWikibaseRepo()->getInternalFormatStatementDeserializer();
		$this->assertInstanceOf( Deserializer::class, $deserializer );
	}

	public function testGetEntityChangeFactory() {
		$factory = WikibaseRepo::getEntityChangeFactory();
		$this->assertInstanceOf( EntityChangeFactory::class, $factory );
	}

	public function testNewItemHandler() {
		$handler = $this->getWikibaseRepo()->newItemHandler();
		$this->assertInstanceOf( EntityHandler::class, $handler );
	}

	public function testNewPropertyHandler() {
		$handler = $this->getWikibaseRepo()->newPropertyHandler();
		$this->assertInstanceOf( EntityHandler::class, $handler );
	}

	public function testNewItemHandler_noTransform() {
		$this->settings->setSetting( 'transformLegacyFormatOnExport', false );
		$wikibaseRepo = $this->getWikibaseRepo();

		$handler = $wikibaseRepo->newItemHandler();
		$this->assertNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewPropertyHandler_noTransform() {
		$this->settings->setSetting( 'transformLegacyFormatOnExport', false );
		$wikibaseRepo = $this->getWikibaseRepo();

		$handler = $wikibaseRepo->newPropertyHandler();
		$this->assertNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewItemHandler_withTransform() {
		$this->settings->setSetting( 'transformLegacyFormatOnExport', true );
		$wikibaseRepo = $this->getWikibaseRepo();

		$handler = $wikibaseRepo->newItemHandler();
		$this->assertNotNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewPropertyHandler_withTransform() {
		$this->settings->setSetting( 'transformLegacyFormatOnExport', true );
		$wikibaseRepo = $this->getWikibaseRepo();

		$handler = $wikibaseRepo->newPropertyHandler();
		$this->assertNotNull( $handler->getLegacyExportFormatDetector() );
	}

	private function getWikibaseRepo() {
		$this->setEntityTypeDefinitions( $this->entityTypeDefinitions );
		$this->setRepoSettings( $this->settings );
		$this->setEntitySourceDefinitions( $this->entitySourceDefinitions );
		return new WikibaseRepo();
	}

	private function getDefaultEntitySourceDefinitions( string $sourceName ) {
		return new EntitySourceDefinitions(
			[
				new EntitySource(
					$sourceName,
					false,
					[
						'item' => [ 'namespaceId' => 100, 'slot' => 'main' ],
						'property' => [ 'namespaceId' => 200, 'slot' => 'main' ],
					],
					'',
					'',
					'',
					''
				)
			],
			$this->entityTypeDefinitions
		);
	}

	public function testGetApiHelperFactory() {
		$factory = $this->getWikibaseRepo()->getApiHelperFactory( new RequestContext() );
		$this->assertInstanceOf( ApiHelperFactory::class, $factory );
	}

	public function testNewEditEntityFactory() {
		$factory = $this->getWikibaseRepo()->newEditEntityFactory( new RequestContext() );
		$this->assertInstanceOf( MediawikiEditEntityFactory::class, $factory );
	}

	public function testNewEditEntityFactory_withoutContextParam() {
		$factory = $this->getWikibaseRepo()->newEditEntityFactory();
		$this->assertInstanceOf( MediawikiEditEntityFactory::class, $factory );
	}

	public function testNewItemMergeInteractor() {
		$interactor = $this->getWikibaseRepo()->newItemMergeInteractor( new RequestContext() );
		$this->assertInstanceOf( ItemMergeInteractor::class, $interactor );
	}

	public function testGetTermLookup() {
		$service = $this->getWikibaseRepo()->getTermLookup();
		$this->assertInstanceOf( TermLookup::class, $service );
	}

	public function testGetTermBuffer() {
		$service = $this->getWikibaseRepo()->getTermBuffer();
		$this->assertInstanceOf( TermBuffer::class, $service );
	}

	public function testGetTermBuffer_instance() {
		$repo = $this->getWikibaseRepo();
		$service = $repo->getTermBuffer();
		$this->assertSame( $service, $repo->getTermBuffer(), 'Second call should return same instance' );
		$this->assertSame( $service, $repo->getTermLookup(), 'TermBuffer and TermLookup should be the same object' );
	}

	public function testNewPropertyInfoBuilder() {
		$this->settings->setSetting( 'formatterUrlProperty', 'P123' );
		$this->settings->setSetting( 'canonicalUriProperty', 'P321' );
		$wikibaseRepo = $this->getWikibaseRepo();

		$builder = $wikibaseRepo->newPropertyInfoBuilder();

		$this->assertInstanceOf( PropertyInfoBuilder::class, $builder );
		$expected = [
			PropertyInfoLookup::KEY_FORMATTER_URL => new PropertyId( 'P123' ),
			PropertyInfoStore::KEY_CANONICAL_URI => new PropertyId( 'P321' )
		];
		$this->assertEquals( $expected,  $builder->getPropertyIdMap() );
	}

	public function testGetEntityNamespaceLookup() {
		$service = $this->getWikibaseRepo()->getEntityNamespaceLookup();
		$this->assertInstanceOf( EntityNamespaceLookup::class, $service );
	}

	public function testGetEntityIdHtmlLinkFormatterFactory() {
		$service = $this->getWikibaseRepo()->getEntityIdHtmlLinkFormatterFactory();
		$this->assertInstanceOf( EntityIdHtmlLinkFormatterFactory::class, $service );
	}

	public function testGetEntityDataFormatProvider() {
		$service = $this->getWikibaseRepo()->getEntityDataFormatProvider();
		$this->assertInstanceOf( EntityDataFormatProvider::class, $service );
	}

	public function testGetEntityDataUriManager() {
		$service = $this->getWikibaseRepo()->getEntityDataUriManager();
		$this->assertInstanceOf( EntityDataUriManager::class, $service );
	}

	public function testGetEntityParserOutputGeneratorFactory() {
		$service = $this->getWikibaseRepo()->getEntityParserOutputGeneratorFactory();
		$this->assertInstanceOf( EntityParserOutputGeneratorFactory::class, $service );
	}

	public function testGetDataTypeValidatorFactory() {
		$service = $this->getWikibaseRepo()->getDataTypeValidatorFactory();
		$this->assertInstanceOf( BuilderBasedDataTypeValidatorFactory::class, $service );
	}

	public function testGetDataTypeDefinitions() {
		$dataTypeDefinitions = WikibaseRepo::getDataTypeDefinitions();
		$this->assertInstanceOf( DataTypeDefinitions::class, $dataTypeDefinitions );
	}

	public function testGetValueSnakRdfBuilderFactory() {
		$factory = WikibaseRepo::getValueSnakRdfBuilderFactory();
		$this->assertInstanceOf( ValueSnakRdfBuilderFactory::class, $factory );
	}

	public function testGetRdfVocabulary() {
		$factory = WikibaseRepo::getRdfVocabulary();
		$this->assertInstanceOf( RdfVocabulary::class, $factory );
	}

	/**
	 * @return DataValueFactory
	 */
	private function getDataValueFactory() {
		$this->entityTypeDefinitions = new EntityTypeDefinitions( [
			'item' => [
				EntityTypeDefinitions::ENTITY_ID_PATTERN => ItemId::PATTERN,
				EntityTypeDefinitions::ENTITY_ID_BUILDER => function ( $serialization ) {
					return new ItemId( $serialization );
				},
			],
		] );

		$this->setEntityTypeDefinitions( $this->entityTypeDefinitions );
		return WikibaseRepo::getDataValueFactory();
	}

	public function dataValueProvider() {
		return [
			'string' => [ new StringValue( 'Test' ) ],
			'unknown' => [ new UnknownValue( [ 'foo' => 'bar' ] ) ],
			'globecoordinate' => [ new GlobeCoordinateValue( new LatLongValue( 2, 3 ), 1 ) ],
			'monolingualtext' => [ new MonolingualTextValue( 'als', 'Test' ) ],
			'unbounded quantity' => [ UnboundedQuantityValue::newFromNumber( 2 ) ],
			'quantity' => [ QuantityValue::newFromNumber( 2 ) ],
			'time' => [ new TimeValue(
				'+1980-10-07T17:33:22Z',
				0,
				0,
				1,
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN
			) ],
			'wikibase-entityid' => [ new EntityIdValue( new ItemId( 'Q13' ) ) ],
		];
	}

	/**
	 * @dataProvider dataValueProvider
	 */
	public function testDataValueSerializationDeserializationRoundtrip( DataValue $expected ) {
		$service = $this->getDataValueFactory();
		$deserialized = $service->newFromArray( $expected->toArray() );

		$this->assertEquals( $expected, $deserialized );
	}

	public function entityIdValueSerializationProvider() {
		return [
			'legacy' => [ [
				'entity-type' => 'item',
				'numeric-id' => 13,
			] ],
			'intermediate' => [ [
				'entity-type' => 'item',
				'numeric-id' => 13,
				'id' => 'Q13',
			] ],
			'new' => [ [
				'id' => 'Q13',
			] ],
		];
	}

	/**
	 * @dataProvider entityIdValueSerializationProvider
	 */
	public function testEntityIdValueDeserialization( array $serialization ) {
		$service = $this->getDataValueFactory();
		$deserialized = $service->newFromArray( [
			'type' => 'wikibase-entityid',
			'value' => $serialization,
		] );

		$expected = new EntityIdValue( new ItemId( 'Q13' ) );
		$this->assertEquals( $expected, $deserialized );
	}

	public function testGetEntityTypeToRepositoryMapping() {
		$this->entityTypeDefinitions = $this->getEntityTypeDefinitionsWithSubentities();
		$this->entitySourceDefinitions = new EntitySourceDefinitions(
			[
				new EntitySource(
					'local',
					false,
					[
						'foo' => [ 'namespaceId' => 200, 'slot' => 'main' ],
						'bar' => [ 'namespaceId' => 220, 'slot' => 'main' ],
					],
					'',
					'',
					'',
					''
				),
				new EntitySource(
					'lexemewiki',
					'bazdb',
					[
						'lexeme' => [ 'namespaceId' => 280, 'slot' => 'main' ],
					],
					'',
					'lex',
					'lex',
					'lexwiki'
				)
			],
			$this->entityTypeDefinitions
		);

		$wikibaseRepo = $this->getWikibaseRepo();

		$this->assertEquals(
			[
				'foo' => [ '' ],
				'bar' => [ '' ],
				'lexeme' => [ '' ],
				'form' => [ '' ],
			],
			$wikibaseRepo->getEntityTypeToRepositoryMapping()
		);
	}

	public function testGetConceptBaseUris() {
		$this->entitySourceDefinitions = new EntitySourceDefinitions( [
			new EntitySource(
				'local',
				false,
				[
					'foo' => [ 'namespaceId' => 200, 'slot' => 'main' ],
					'bar' => [ 'namespaceId' => 220, 'slot' => 'main' ],
				],
				'http://local.wiki/entity/',
				'',
				'',
				''
			),
			new EntitySource(
				'bazwiki',
				'bazdb',
				[
					'baz' => [ 'namespaceId' => 250, 'slot' => 'main' ],
				],
				'http://baz.wiki/entity/',
				'baz',
				'baz',
				'bazwiki'
			)
		], $this->entityTypeDefinitions );

		$wikibaseRepo = $this->getWikibaseRepo();

		$this->assertEquals(
			[ 'local' => 'http://local.wiki/entity/', 'bazwiki' => 'http://baz.wiki/entity/' ],
			$wikibaseRepo->getConceptBaseUris()
		);
	}

	public function testParameterLessFunctionCalls() {
		// Make sure (as good as we can) that all functions can be called without
		// exceptions/ fatals and nothing accesses the database or does http requests.
		$wbRepo = $this->getWikibaseRepo();

		$reflectionClass = new ReflectionClass( $wbRepo );
		$publicMethods = $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC );
		$federatedPropertyMethods = $this->getFederatedPropertyMethodNames();

		foreach ( $publicMethods as $publicMethod ) {
			if ( in_array( $publicMethod->name, $federatedPropertyMethods ) ) {
				// These methods always throw an exception if the feature is disabled
				// These methods are checked in testParameterLessFunctionCallsForFederatedProperties
				continue;
			}
			$this->invokeMethodIfNoRequiredParameters( $wbRepo, $publicMethod );
		}
	}

	public function testNewFederatedPropertiesServiceFactoryDoesntFatal() {
		// Make sure (as good as we can) that all functions can be called without
		// exceptions/ fatals and nothing accesses the database or does http requests.
		$this->settings->setSetting( 'federatedPropertiesEnabled', true );
		$wbRepo = $this->getWikibaseRepo();

		$wbRepo->newFederatedPropertiesServiceFactory();
		$this->addToAssertionCount( 1 );
	}

	public function provideParameterLessFunctionCallsForFederatedPropertiesThrowExceptionWhenDisabled() {
		$methods = $this->getFederatedPropertyMethodNames();
		return array_map(
			function( $a ) {
				return [ $a ];
			},
			$methods
		);
	}

	/**
	 * @dataProvider provideParameterLessFunctionCallsForFederatedPropertiesThrowExceptionWhenDisabled
	 */
	public function testParameterLessFunctionCallsForFederatedPropertiesThrowExceptionWhenDisabled( $methodName ) {
		// Make sure (as good as we can) that all functions can be called without
		// exceptions/ fatals and nothing accesses the database or does http requests.
		$this->settings->setSetting( 'federatedPropertiesEnabled', false );
		$wbRepo = $this->getWikibaseRepo();

		$reflectionClass = new ReflectionClass( $wbRepo );

		$this->expectException( LogicException::class );
		$this->invokeMethodIfNoRequiredParameters( $wbRepo, $reflectionClass->getMethod( $methodName ) );
	}

	private function invokeMethodIfNoRequiredParameters( $wbRepo, $method ) {
		if ( $method->getNumberOfRequiredParameters() === 0 ) {
			$method->invoke( $wbRepo );
		}
	}

	public function entitySourceBasedFederationProvider() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * @dataProvider entitySourceBasedFederationProvider
	 */
	public function testWikibaseServicesParameterLessFunctionCalls( $entitySourceBasedFederation ) {
		$this->settings->setSetting(
			'repositories',
			[ '' => [
				'database' => 'dummy',
				'base-uri' => null,
				'prefix-mapping' => [ '' => '' ],
				'entity-namespaces' => $this->settings->getSetting( 'entityNamespaces' ),
			] ]
		);
		$this->settings->setSetting( 'useEntitySourceBasedFederation', $entitySourceBasedFederation );

		// override services in preparation for getting the WikibaseServices below
		$this->getWikibaseRepo();

		// Make sure (as good as we can) that all functions can be called without
		// exceptions/ fatals and nothing accesses the database or does http requests.
		$wbRepoServices = WikibaseRepo::getWikibaseServices();

		$reflectionClass = new ReflectionClass( $wbRepoServices );
		$publicMethods = $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC );

		foreach ( $publicMethods as $publicMethod ) {
			if ( $publicMethod->getNumberOfRequiredParameters() === 0 ) {
				$publicMethod->invoke( $wbRepoServices );
			}
		}
	}

	public function testLinkTargetEntityIdLookup() {
		$this->assertInstanceOf(
			LinkTargetEntityIdLookup::class,
			$this->getWikibaseRepo()->getLinkTargetEntityIdLookup()
		);
	}

	/**
	 * These methods should throw a Runtime exception when called without enabling the feature.
	 * @return string[]
	 */
	private function getFederatedPropertyMethodNames() {
		return [
			'newFederatedPropertiesServiceFactory'
		];
	}

	public function testGetEntitySourceDefinitions() {
		$entitySourceDefinitions = WikibaseRepo::getEntitySourceDefinitions();
		$this->assertInstanceOf( EntitySourceDefinitions::class, $entitySourceDefinitions );
	}

}
