<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Wikibase\Repo\FederatedProperties\ApiRequestExecutionException;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;

/**
 * @covers \Wikibase\Repo\FederatedProperties\GenericActionApiClient
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GenericActionApiClientTest extends \PHPUnit\Framework\TestCase {

	public function testGetBuildsUrlCorrectly() {
		$apiUrl = 'https://wikidata.org/w/api.php';
		$params = [ 'a-param' => 'a value', 'another-param' => 'another value' ];
		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->with( $apiUrl . '?' . http_build_query( $params ) )
			->willReturn( $this->newMockResponseWithHeaders() );

		$api = new GenericActionApiClient(
			$requestFactory,
			$apiUrl,
			new NullLogger()
		);
		$api->get( $params );
	}

	public function testGetReturnsAdaptedResponse() {
		$headers = [ 'Some-header' => [ 'some value' ] ];
		$mwResponse = $this->newMockResponseWithHeaders( $headers );

		$api = new GenericActionApiClient(
			$this->newMockRequestFactory( $mwResponse ),
			'https://does-not-matter/',
			new NullLogger()
		);
		$response = $api->get( [] );

		$this->assertEquals( $headers, $response->getHeaders() );
	}

	public function testGetRequestIsLogged() {
		$apiUrl = 'https://wikidata.org/w/api.php';
		$params = [ 'a-param' => 'a value', 'another-param' => 'another value' ];
		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $this->newMockResponseWithHeaders() );

		$logger = new TestLogger();

		$api = new GenericActionApiClient(
			$requestFactory,
			$apiUrl,
			$logger
		);
		$api->get( $params );

		$logger->hasDebugThatContains( 'https://wikidata.org/w/api.php' );
	}

	public function testGivenRequestHitsTimeout_throwsException() {
		$response = $this->newMockResponseWithHeaders();
		$response->expects( $this->once() )
			->method( 'getStatus' )
			->willReturn( 0 );
		$api = new GenericActionApiClient(
			$this->newMockRequestFactory( $response ),
			'https://wikidata.org/w/api.php',
			new NullLogger()
		);

		$this->expectException( ApiRequestExecutionException::class );
		$api->get( [] );
	}

	private function newMockResponseWithHeaders( array $headers = [ 'Some-header' => [ 'some' ] ] ) {
		$mwResponse = $this->createMock( MWHttpRequest::class );
		$mwResponse->expects( $this->any() )
			->method( 'getResponseHeaders' )
			->willReturn( $headers );

		return $mwResponse;
	}

	private function newMockRequestFactory( $mwResponse ) {
		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $mwResponse );

		return $requestFactory;
	}

}
