<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use GuzzleHttp\Psr7\Response;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\FederatedProperties\ApiEntityLookup;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;
use Wikimedia\Assert\ParameterElementTypeException;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiEntityLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiEntityLookupTest extends TestCase {

	private $responseDataFiles = [
		'p18-en' => 'api-prefetching-term-lookup-test-data-p18-en.json',
		'p1-missing' => 'api-entity-lookup-p1-missing.json',
	];

	private $data = [];

	private $p1;
	private $p11;
	private $p18;

	protected function setUp(): void {
		parent::setUp();
		// Load data files once at the start of tests rather than for each test case
		foreach ( $this->responseDataFiles as $key => $file ) {
			$content = file_get_contents( __DIR__ . '/../../data/federatedProperties/' . $file );
			$this->data[$file] = json_decode( $content, true );
		}
		$this->p1 = new PropertyId( 'P1' );
		$this->p11 = new PropertyId( 'P11' );
		$this->p18 = new PropertyId( 'P18' );
	}

	public function testFetchEntitiesDoesNotAllowStrings() {
		$apiEntityLookup = new ApiEntityLookup( $this->createMock( GenericActionApiClient::class ) );
		$this->expectException( ParameterElementTypeException::class );
		$apiEntityLookup->fetchEntities( [ 'P1', 'P2' ] );
	}

	public function testGetResultForIdReturnsEntityResponseData() {
		$api = $this->newMockApi( $this->responseDataFiles['p18-en'], [ $this->p18 ] );
		$apiEntityLookup = new ApiEntityLookup( $api );
		$apiEntityLookup->fetchEntities( [ $this->p18 ] );

		$this->assertEquals(
			$this->data[$this->responseDataFiles['p18-en']]['entities']['P18'],
			$apiEntityLookup->getResultPartForId( $this->p18 )
		);
	}

	public function testGivenEntityIsMissing_GetResultPartForIdReturnsMissing() {
		$api = $this->newMockApi( $this->responseDataFiles['p1-missing'], [ $this->p1 ] );
		$apiEntityLookup = new ApiEntityLookup( $api );
		$apiEntityLookup->fetchEntities( [ $this->p1 ] );

		$this->assertEquals(
			$this->data[$this->responseDataFiles['p1-missing']]['entities']['P1'],
			$apiEntityLookup->getResultPartForId( $this->p1 )
		);
	}

	public function testGivenEntityHasNotBeenFetched_GetResultPartForIdThrowsException() {
		$apiEntityLookup = new ApiEntityLookup( $this->createMock( GenericActionApiClient::class ) );
		$this->expectException( LogicException::class );
		$apiEntityLookup->getResultPartForId( $this->p11 );
	}

	public function testFetchEntitiesDoesNotRepeatedlyFetchEntities() {
		$api = $this->newMockApi( $this->responseDataFiles['p18-en'], [ $this->p18 ] );
		$apiEntityLookup = new ApiEntityLookup( $api );

		$apiEntityLookup->fetchEntities( [ $this->p18 ] );
		$apiEntityLookup->fetchEntities( [ $this->p18 ] ); // should not trigger another api call

		$this->assertEquals(
			$this->data[$this->responseDataFiles['p18-en']]['entities']['P18'],
			$apiEntityLookup->getResultPartForId( $this->p18 )
		);
	}

	public function testGivenFetchEntitiesCalledRepeatedly_requestsOnlyNotPreviouslyFetchedEntities() {
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ $this->getRequestParameters( [ $this->p1 ] ) ],
				[ $this->getRequestParameters( [ $this->p18 ] ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->newResponse( $this->responseDataFiles['p1-missing'] ),
				$this->newResponse( $this->responseDataFiles['p18-en'] )
			);

		$entityLookup = new ApiEntityLookup( $api );

		$entityLookup->fetchEntities( [ $this->p1 ] );
		$entityLookup->fetchEntities( [ $this->p1, $this->p18 ] );
	}

	private function newMockApi( string $responseDataFile, array $ids ) : GenericActionApiClient {
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $this->getRequestParameters( $ids ) )
			->willReturn( $this->newResponse( $responseDataFile ) );

		return $api;
	}

	private function getRequestParameters( array $ids ): array {
		return [
			'action' => 'wbgetentities',
			'ids' => implode( '|', $ids ),
			'props' => 'labels|descriptions|datatype',
			'format' => 'json'
		];
	}

	private function newResponse( string $responseDataFile ): Response {
		return new Response( 200, [], json_encode( $this->data[$responseDataFile] ) );
	}

}
