<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Repo\FederatedProperties\ApiEntityLookup;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;
use Wikibase\Repo\Tests\HttpResponseMockerTrait;
use Wikimedia\Assert\ParameterElementTypeException;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiEntityLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiEntityLookupTest extends TestCase {

	use HttpResponseMockerTrait;

	private $responseDataFiles = [
		'p18-en' => 'api-prefetching-term-lookup-test-data-p18-en.json',
		'p31-en-de' => 'api-prefetching-term-lookup-test-data-p31-en-de.json',
		'p1-missing' => 'wbgetentities-p1-missing.json',
	];

	private $data = [];

	private const CONCEPT_BASE_URI = 'http://wikidata.org/entity/';

	private $fp1;
	private $fp11;
	private $fp18;
	private $fp31;

	protected function setUp(): void {
		parent::setUp();
		// Load data files once at the start of tests rather than for each test case
		foreach ( $this->responseDataFiles as $key => $file ) {
			$content = file_get_contents( __DIR__ . '/../../data/federatedProperties/' . $file );
			$this->data[$file] = json_decode( $content, true );
		}
		$this->fp1 = new FederatedPropertyId( self::CONCEPT_BASE_URI . 'P1', 'P1' );
		$this->fp11 = new FederatedPropertyId( self::CONCEPT_BASE_URI . 'P11', 'P11' );
		$this->fp18 = new FederatedPropertyId( self::CONCEPT_BASE_URI . 'P18', 'P18' );
		$this->fp31 = new FederatedPropertyId( self::CONCEPT_BASE_URI . 'P31', 'P31' );
	}

	public function testFetchEntitiesDoesNotAllowStrings() {
		$apiEntityLookup = new ApiEntityLookup(
			$this->createMock( GenericActionApiClient::class )
		);
		$this->expectException( ParameterElementTypeException::class );
		$apiEntityLookup->fetchEntities( [ 'http://wikidata.org/entity/P1', 'http://wikidata.org/entity/P2' ] );
	}

	public function testGetResultForIdReturnsEntityResponseData() {
		$api = $this->newMockApi( $this->responseDataFiles['p18-en'], [ 'P18' ] );
		$apiEntityLookup = new ApiEntityLookup( $api );
		$apiEntityLookup->fetchEntities( [ $this->fp18 ] );

		$this->assertEquals(
			$this->data[$this->responseDataFiles['p18-en']]['entities']['P18'],
			$apiEntityLookup->getResultPartForId( $this->fp18 )
		);
	}

	public function testGivenEntityIsMissing_GetResultPartForIdReturnsMissing() {
		$api = $this->newMockApi( $this->responseDataFiles['p1-missing'], [ 'P1' ] );
		$apiEntityLookup = new ApiEntityLookup( $api );
		$apiEntityLookup->fetchEntities( [ $this->fp1 ] );

		$this->assertEquals(
			$this->data[$this->responseDataFiles['p1-missing']]['entities']['P1'],
			$apiEntityLookup->getResultPartForId( $this->fp1 )
		);
	}

	public function testGivenEntityHasNotBeenFetched_GetResultPartForIdThrowsException() {
		$this->markTestSkipped( 'This is desired behaviour, but not yet implemented. T253125.' );
		$apiEntityLookup = new ApiEntityLookup( $this->createMock(
			GenericActionApiClient::class )
		);
		$this->expectException( LogicException::class );
		$apiEntityLookup->getResultPartForId( $this->fp11 );
	}

	public function testGivenEntityHasNotBeenFetched_GetResultPartForIdFetches() {
		$api = $this->newMockApi( $this->responseDataFiles['p18-en'], [ 'P18' ] );
		$apiEntityLookup = new ApiEntityLookup( $api );
		$resultPart = $apiEntityLookup->getResultPartForId( $this->fp18 );
		$this->assertEquals(
			$this->data[$this->responseDataFiles['p18-en']]['entities']['P18'],
			$resultPart
		);
	}

	public function testFetchEntitiesDoesNotRepeatedlyFetchEntities() {
		$api = $this->newMockApi( $this->responseDataFiles['p18-en'], [ 'P18' ] );
		$apiEntityLookup = new ApiEntityLookup( $api );

		$apiEntityLookup->fetchEntities( [ $this->fp18 ] );
		$apiEntityLookup->fetchEntities( [ $this->fp18 ] ); // should not trigger another api call

		$this->assertEquals(
			$this->data[$this->responseDataFiles['p18-en']]['entities']['P18'],
			$apiEntityLookup->getResultPartForId( $this->fp18 )
		);
	}

	public function testFetchEntitiesBatchesInChunks() {
		// Api mock that ensures 2 api calls, but only bothers returning 2 entities (that can be used to ensure results are merged)
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturn(
				$this->newMockResponse( json_encode( $this->data[ $this->responseDataFiles[ 'p18-en' ] ] ), 200 ),
				$this->newMockResponse( json_encode( $this->data[ $this->responseDataFiles[ 'p31-en-de' ] ] ), 200 )
			);

		// Generate a list of 60 ids to fetch, as 50 is the batch size
		$toFetch = [];
		foreach ( range( 1, 60 ) as $number ) {
			$toFetch[] = new FederatedPropertyId( 'http://wikidata.org/entity/P' . $number, "P$number" );
		}

		$apiEntityLookup = new ApiEntityLookup( $api );
		$apiEntityLookup->fetchEntities( $toFetch );

		// Fetching out results that were mocked though not result in an exception, which means the results were correctly merged
		$apiEntityLookup->getResultPartForId( $this->fp18 );
		$apiEntityLookup->getResultPartForId( $this->fp31 );
	}

	public function testGivenFetchEntitiesCalledRepeatedly_requestsOnlyNotPreviouslyFetchedEntities() {
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ $this->getRequestParameters( [ 'P1' ] ) ],
				[ $this->getRequestParameters( [ 'P18' ] ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->newMockResponse( json_encode( $this->data[ $this->responseDataFiles[ 'p1-missing' ] ] ), 200 ),
				$this->newMockResponse( json_encode( $this->data[ $this->responseDataFiles[ 'p18-en' ] ] ), 200 )
			);

		$entityLookup = new ApiEntityLookup( $api );

		$entityLookup->fetchEntities( [ $this->fp1 ] );
		$entityLookup->fetchEntities( [ $this->fp1, $this->fp18 ] );
	}

	private function newMockApi( string $responseDataFile, array $ids ): GenericActionApiClient {
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $this->getRequestParameters( $ids ) )
			->willReturn( $this->newMockResponse( json_encode( $this->data[ $responseDataFile ] ), 200 ) );

		return $api;
	}

	private function getRequestParameters( array $ids ): array {
		return [
			'action' => 'wbgetentities',
			'ids' => implode( '|', $ids ),
			'props' => 'labels|descriptions|datatype',
			'format' => 'json',
		];
	}

}
