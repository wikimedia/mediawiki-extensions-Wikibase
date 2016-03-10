<?php

namespace Wikibase\Test;

use DataValues\Serializers\DataValueSerializer;
use DerivativeContext;
use FauxRequest;
use FauxResponse;
use HashSiteStore;
use HttpError;
use OutputPage;
use RequestContext;
use SiteList;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataRequestHandler;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\LinkedData\EntityDataRequestHandler
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseEntityData
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityDataRequestHandlerTest extends \MediaWikiTestCase {

	/**
	 * @var Title
	 */
	private $interfaceTitle;

	/**
	 * @var int
	 */
	private $obLevel;

	protected function setUp() {
		parent::setUp();

		$this->interfaceTitle = Title::newFromText( "Special:EntityDataRequestHandlerTest" );

		$this->obLevel = ob_get_level();
	}

	protected function tearDown() {
		$obLevel = ob_get_level();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}

		if ( $obLevel !== $this->obLevel ) {
			$this->fail( "Test changed output buffer level: was {$this->obLevel} before test, but $obLevel after test." );
		}

		parent::tearDown();
	}

	/**
	 * @return EntityDataRequestHandler
	 */
	protected function newHandler() {
		$mockRepository = EntityDataTestProvider::getMockRepository();
		$idParser = new BasicEntityIdParser(); // we only test for items and properties here.

		$dataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'string' ) );

		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getEntityType() . ':' . $id->getSerialization() );
			} ) );

		$propertyLookup = $this->getMock( PropertyDataTypeLookup::class );

		$entityDataFormatProvider = new EntityDataFormatProvider();
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		// Note: We are testing with the actual RDF bindings. These should not change for well
		// known data types. Mocking the bindings would be nice, but is complex and not needed.
		$rdfBuilder = WikibaseRepo::getDefaultInstance()->getValueSnakRdfBuilderFactory();

		$service = new EntityDataSerializationService(
			$mockRepository,
			$titleLookup,
			$propertyLookup,
			$rdfBuilder,
			new SiteList(),
			$entityDataFormatProvider,
			$serializerFactory,
			new HashSiteStore(),
			new RdfVocabulary(
				EntityDataSerializationServiceTest::URI_BASE,
				EntityDataSerializationServiceTest::URI_DATA
			)
		);

		$entityDataFormatProvider->setFormatWhiteList(
			array(
				// using the API
				'json', // default
				'php',

				// using easyRdf
				'rdfxml',
				'n3',
				'turtle',
				'ntriples',
			)
		);

		$extensions = array(
			// using the API
			'json' => 'json', // default
			'php' => 'php',

			// using easyRdf
			'rdfxml' => 'rdf',
			'n3' => 'n3',
			'turtle' => 'ttl',
			'ntriples' => 'n3',
		);

		$uriManager = new EntityDataUriManager(
			$this->interfaceTitle,
			$extensions,
			$titleLookup
		);

		$handler = new EntityDataRequestHandler(
			$uriManager,
			$titleLookup,
			$idParser,
			$mockRepository,
			$mockRepository,
			$service,
			$entityDataFormatProvider,
			'json',
			1800,
			false,
			null
		);

		return $handler;
	}

	/**
	 * @param array $params
	 * @param string[] $headers
	 *
	 * @return OutputPage
	 */
	protected function makeOutputPage( array $params, array $headers ) {
		// construct request
		$request = new FauxRequest( $params );
		$request->response()->header( 'Status: 200 OK', true, 200 ); // init/reset

		foreach ( $headers as $name => $value ) {
			$request->setHeader( strtoupper( $name ), $value );
		}

		// construct Context and OutputPage
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );

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
		$subpage,
		array $params,
		array $headers,
		$expectedOutput,
		$expectedStatusCode = 200,
		array $expectedHeaders = array()
	) {
		$output = $this->makeOutputPage( $params, $headers );
		$request = $output->getRequest();

		/* @var FauxResponse $response */
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
			$this->assertRegExp( $expectedOutput, $text, 'output' );

			foreach ( $expectedHeaders as $name => $exp ) {
				$value = $response->getheader( $name );
				$this->assertNotNull( $value, "header: $name" );
				$this->assertInternalType( 'string', $value, "header: $name" );
				$this->assertRegExp( $exp, $value, "header: $name" );
			}
		} catch ( HttpError $e ) {
			ob_end_clean();
			$this->assertEquals( $expectedStatusCode, $e->getStatusCode(), 'status code' );
			$this->assertRegExp( $expectedOutput, $e->getHTML(), 'error output' );
		}
	}

	//TODO: test canHandleRequest
	//TODO: test httpContentNegotiation
	//TODO: test getCanonicalFormat
	//TODO: test ALL the things!
}
