<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\LinkedData;

use DataValues\Serializers\DataValueSerializer;
use DerivativeContext;
use FauxRequest;
use FauxResponse;
use HashSiteStore;
use HtmlCacheUpdater;
use HttpError;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use OutputPage;
use Psr\Log\NullLogger;
use RequestContext;
use Title;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataRequestHandler;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\LinkedData\EntityDataRequestHandler
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseEntityData
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityDataRequestHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var Title
	 */
	private $interfaceTitle;

	/**
	 * @var int
	 */
	private $obLevel;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var string[][]
	 */
	private $subEntityTypesMap;

	protected function setUp(): void {
		parent::setUp();

		$this->interfaceTitle = Title::makeTitle( NS_SPECIAL, 'EntityDataRequestHandlerTest' );
		// ensure the namespace name doesn’t get translated
		$this->setMwGlobals( 'wgLanguageCode', 'qqx' );

		$this->obLevel = ob_get_level();

		$this->entityRevisionLookup = null; // `newHandler` uses a MockRepository unless this is set
		$this->subEntityTypesMap = [];
	}

	protected function tearDown(): void {
		$obLevel = ob_get_level();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}

		if ( $obLevel !== $this->obLevel ) {
			$this->fail( "Test changed output buffer level: was {$this->obLevel} before test, but $obLevel after test." );
		}

		parent::tearDown();
	}

	protected function newHandler(): EntityDataRequestHandler {
		global $wgScriptPath;

		$mockRepository = EntityDataTestProvider::getMockRepository();

		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturn( 'string' );

		$entityTitleStoreLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleStoreLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				return Title::newFromTextThrow( $id->getEntityType() . ':' . $id->getSerialization() );
			} );

		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		// should also be unused since we configure no page props
		$entityContentFactory->expects( $this->never() )
			->method( 'newFromEntity' );

		$entityDataFormatProvider = new EntityDataFormatProvider();
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		// Note: We are testing with the actual RDF bindings. These should not change for well
		// known data types. Mocking the bindings would be nice, but is complex and not needed.

		$rdfBuilderFactory = new RdfBuilderFactory(
			new RdfVocabulary(
				[ 'test' => EntityDataSerializationServiceTest::URI_BASE ],
				[ 'test' => EntityDataSerializationServiceTest::URI_DATA ],
				new EntitySourceDefinitions( [
					new DatabaseEntitySource(
						'test',
						'testdb',
						[ 'item' => [ 'namespaceId' => 1200, 'slot' => SlotRecord::MAIN ] ],
						EntityDataSerializationServiceTest::URI_BASE,
						'wd',
						'',
						''
					),
				], new SubEntityTypesMapper( [] ) ),
				[ 'test' => 'wd' ],
				[ 'test' => '' ]
			),
			WikibaseRepo::getEntityRdfBuilderFactory(),
			$entityContentFactory,
			WikibaseRepo::getEntityStubRdfBuilderFactory(),
			$mockRepository
		);

		$service = new EntityDataSerializationService(
			$entityTitleStoreLookup,
			new InMemoryDataTypeLookup(),
			$entityDataFormatProvider,
			$serializerFactory,
			$serializerFactory->newItemSerializer(),
			new HashSiteStore(),
			$rdfBuilderFactory,
			WikibaseRepo::getEntityIdParser()
		);

		$entityDataFormatProvider->setAllowedFormats(
			[
				// using the API
				'json', // default
				'php',

				// using purtle
				'rdfxml',
				'n3',
				'turtle',
				'ntriples',
				'jsonld',
			]
		);

		$extensions = [
			// using the API
			'json' => 'json', // default
			'php' => 'php',

			// using purtle
			'rdfxml' => 'rdf',
			'n3' => 'n3',
			'turtle' => 'ttl',
			'ntriples' => 'n3',
			'jsonld' => 'jsonld',
		];

		$uriManager = new EntityDataUriManager(
			$this->interfaceTitle,
			$extensions,
			[
				// “Special” needs no translation because we override the content language
				$wgScriptPath . '/index.php?title=Special:EntityDataRequestHandlerTest' .
				'/{entity_id}.json&revision={revision_id}',
			],
			$entityTitleStoreLookup
		);
		$mockHtmlCacheUpdater = $this->createMock( HtmlCacheUpdater::class );

		$entityTypesWithoutRdfOutput = [ 'property' ];

		$handler = new EntityDataRequestHandler(
			$uriManager,
			$mockHtmlCacheUpdater,
			WikibaseRepo::getEntityIdParser(),
			$this->entityRevisionLookup ?? $mockRepository,
			$mockRepository,
			$service,
			$entityDataFormatProvider,
			new NullLogger(),
			$entityTypesWithoutRdfOutput,
			'json',
			1800,
			false,
			null,
			new SubEntityTypesMapper( $this->subEntityTypesMap )
		);

		return $handler;
	}

	/**
	 * @param array $params
	 * @param string[] $headers
	 */
	protected function makeOutputPage( array $params, array $headers ): OutputPage {
		// construct request
		$request = new FauxRequest( $params );
		$request->setRequestURL( 'https://repo.example/wiki/Special:EntityData/Q1.ttl' );
		$request->response()->header( 'Status: 200 OK', true, 200 ); // init/reset

		foreach ( $headers as $name => $value ) {
			$request->setHeader( strtoupper( $name ), $value );
		}

		// construct Context and OutputPage
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setLanguage( 'qqx' );

		$output = new OutputPage( $context );
		$output->setTitle( $this->interfaceTitle );
		$context->setOutput( $output );

		return $output;
	}

	public function handleRequestProvider() {
		return EntityDataTestProvider::provideHandleRequest();
	}

	/**
	 * @dataProvider handleRequestProvider
	 *
	 * @param string $subpage The subpage to request (or '')
	 * @param array  $params  Request parameters
	 * @param array  $headers  Request headers
	 * @param string $expectedOutput Regex to match the output against.
	 * @param int $expectedStatusCode Expected HTTP status code.
	 * @param string[] $expectedHeaders Expected HTTP response headers.
	 */
	public function testHandleRequest(
		string $subpage,
		array $params,
		array $headers,
		string $expectedOutput,
		int $expectedStatusCode = 200,
		array $expectedHeaders = []
	): void {
		$output = $this->makeOutputPage( $params, $headers );
		$request = $output->getRequest();

		/** @var FauxResponse $response */
		$response = $request->response();

		// construct handler
		$handler = $this->newHandler();

		try {
			ob_start();
			$handler->handleRequest( $subpage, $request, $output );

			if ( $output->getRedirect() !== '' ) {
				// hack to apply redirect to web response
				$output->output();
			}

			$text = ob_get_contents();
			ob_end_clean();

			$this->assertEquals( $expectedStatusCode, $response->getStatusCode(), 'status code' );
			$this->assertMatchesRegularExpression( $expectedOutput, $text, 'output' );

			foreach ( $expectedHeaders as $name => $exp ) {
				$value = $response->getHeader( $name );
				$this->assertNotNull( $value, "header: $name" );
				$this->assertIsString( $value, "header: $name" );
				$this->assertMatchesRegularExpression( $exp, $value, "header: $name" );
			}
		} catch ( HttpError $e ) {
			ob_end_clean();
			$this->assertEquals( $expectedStatusCode, $e->getStatusCode(), 'status code' );
			$this->assertMatchesRegularExpression( $expectedOutput, $e->getHTML(), 'error output' );
		}

		// We always set "Access-Control-Allow-Origin: *"
		$this->assertSame( '*', $response->getHeader( 'Access-Control-Allow-Origin' ) );
	}

	public function testHandleRequestWith304(): void {
		$output = $this->makeOutputPage( [], [ 'If-Modified-Since' => '20131213141516' ] );
		$request = $output->getRequest();

		/** @var FauxResponse $response */
		$response = $request->response();

		// construct handler
		$handler = $this->newHandler();
		$handler->handleRequest( 'Q42.json', $request, $output );
		$text = $output->output( true );

		$this->assertSame( 304, $response->getStatusCode(), 'status code' );
		$this->assertSame( '', $text, 'output' );

		// We always set "Access-Control-Allow-Origin: *"
		$this->assertSame( '*', $response->getHeader( 'Access-Control-Allow-Origin' ) );
	}

	public function provideHttpContentNegotiation(): iterable {
		$q13 = new ItemId( 'Q13' );
		return [
			'No Accept Header' => [
				$q13,
				[], // headers
				'Q13.json',
			],
			'Accept Header without weights' => [
				$q13,
				[ 'ACCEPT' => '*/*, text/html, text/x-wiki' ], // headers
				'Q13',
			],
			'Accept Header with weights' => [
				$q13,
				[ 'ACCEPT' => 'text/*; q=0.5, text/json; q=0.7, application/rdf+xml; q=0.8' ], // headers
				'Q13.rdf',
			],
		];
	}

	/**
	 * @dataProvider provideHttpContentNegotiation
	 *
	 * @param EntityId $id
	 * @param array $headers Request headers
	 * @param string $expectedRedirectSuffix Expected suffix of the HTTP Location header.
	 */
	public function testHttpContentNegotiation(
		EntityId $id,
		array $headers,
		string $expectedRedirectSuffix
	): void {
		/** @var FauxResponse $response */
		$output = $this->makeOutputPage( [], $headers );
		$request = $output->getRequest();

		$handler = $this->newHandler();
		$handler->httpContentNegotiation( $request, $output, $id );

		$this->assertStringEndsWith(
			$expectedRedirectSuffix,
			$output->getRedirect(),
			'redirect target'
		);
	}

	public function testCacheHeaderIsSetWithRevision(): void {
		$params = [ 'revision' => EntityDataTestProvider::ITEM_REVISION_ID ];
		$subpage = 'Q42.json';
		$output = $this->makeOutputPage( $params, [] );
		/** @var FauxRequest $request */
		$request = $output->getRequest();
		'@phan-var FauxRequest $request';
		$request->setRequestUrl(
			$this->interfaceTitle->getSubpage( $subpage )->getLocalURL( $params ) );

		/** @var FauxResponse $response */
		$response = $request->response();

		$handler = $this->newHandler();
		ob_start();
		$handler->handleRequest( $subpage, $request, $output );
		ob_end_clean();

		$this->assertStringContainsString( 'public', $response->getHeader( 'Cache-Control' ) );
	}

	public function testCacheHeaderIsNotSetWithoutRevision(): void {
		$params = [];
		$subpage = 'Q42.json';
		$output = $this->makeOutputPage( $params, [] );
		/** @var FauxRequest $request */
		$request = $output->getRequest();
		'@phan-var FauxRequest $request';
		$request->setRequestUrl(
			$this->interfaceTitle->getSubpage( $subpage )->getLocalURL( $params ) );

		/** @var FauxResponse $response */
		$response = $request->response();

		$handler = $this->newHandler();
		ob_start();
		$handler->handleRequest( $subpage, $request, $output );
		ob_end_clean();

		$this->assertStringContainsString( 'no-cache', $response->getHeader( 'Cache-Control' ) );
		$this->assertStringContainsString( 'private', $response->getHeader( 'Cache-Control' ) );
	}

	public function testGivenUnresolvableSubEntityRedirect_throwsHttpError(): void {
		$subEntityType = 'someSubEntityType';
		$subEntityId = $this->createStub( EntityId::class ); // e.g. a Form or Sense
		$subEntityId->method( 'getEntityType' )->willReturn( $subEntityType );
		$subEntityId->method( 'getSerialization' )->willReturn( 'L123-F1' );

		$parentEntityType = 'someTopLevelEntityType';
		$parentRedirectTarget = $this->createStub( EntityId::class ); // e.g. a Lexeme
		$parentRedirectTarget->method( 'getEntityType' )->willReturn( $parentEntityType );
		$parentRedirectTarget->method( 'getSerialization' )->willReturn( 'L456' );

		$this->subEntityTypesMap = [ $parentEntityType => [ $subEntityType ] ];
		$revision = 777;
		$output = $this->makeOutputPage( [], [] );

		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $subEntityId, $revision )
			->willThrowException( new RevisionedUnresolvedRedirectException(
				$subEntityId,
				$parentRedirectTarget
			) );

		$this->expectException( HttpError::class );
		$this->expectExceptionMessage( 'wikibase-entitydata-unresolvable-sub-entity-redirect: L123-F1, L456' );

		$this->newHandler()->showData( $output->getRequest(), $output, 'json', $subEntityId, $revision );
	}

	//TODO: test canHandleRequest
	//TODO: test getCanonicalFormat
	//TODO: test ALL the things!
}
