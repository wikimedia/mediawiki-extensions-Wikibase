<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use GuzzleHttp\Psr7\Response;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\FederatedProperties\ApiEntityLookup;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;

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

	protected function setUp(): void {
		parent::setUp();
		// Load data files once at the start of tests rather than for each test case
		foreach ( $this->responseDataFiles as $key => $file ) {
			$content = file_get_contents( __DIR__ . '/../../data/federatedProperties/' . $file );
			$this->data[$file] = json_decode( $content, true );
		}
	}

	public function testGetResultForIdReturnsEntityResponseData() {
		$api = $this->newMockApi( $this->responseDataFiles['p18-en'], [ 'P18' ] );
		$apiEntityLookup = new ApiEntityLookup( $api );
		$apiEntityLookup->fetchEntities( [ 'P18' ] );

		$this->assertEquals(
			$this->data[$this->responseDataFiles['p18-en']]['entities']['P18'],
			$apiEntityLookup->getResultPartForId( new PropertyId( 'P18' ) )
		);
	}

	public function testGivenEntityIsMissing_GetResultPartForIdReturnsMissing() {
		$api = $this->newMockApi( $this->responseDataFiles['p1-missing'], [ 'P1' ] );
		$apiEntityLookup = new ApiEntityLookup( $api );
		$apiEntityLookup->fetchEntities( [ 'P1' ] );

		$this->assertEquals(
			$this->data[$this->responseDataFiles['p1-missing']]['entities']['P1'],
			$apiEntityLookup->getResultPartForId( new PropertyId( 'P1' ) )
		);
	}

	public function testGivenEntityHasNotBeenFetched_GetResultPartForIdThrowsException() {
		$apiEntityLookup = new ApiEntityLookup( $this->createMock( GenericActionApiClient::class ) );
		$this->expectException( LogicException::class );
		$propertyId = new PropertyId( 'P11' );
		$apiEntityLookup->getResultPartForId( $propertyId );
	}

	private function newMockApi( $responseDataFile, $ids ) {
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $this->getRequestParameters( $ids ) )
			->willReturn( new Response(
				200,
				[],
				json_encode( $this->data[$responseDataFile] )
			) );

		return $api;
	}

	private function getRequestParameters( $ids ) {
		return [
			'action' => 'wbgetentities',
			'ids' => implode( '|', $ids ),
			'props' => 'labels|descriptions|datatype',
			'format' => 'json'
		];
	}

}
