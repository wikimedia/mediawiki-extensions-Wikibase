<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
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

		$api = new GenericActionApiClient( $requestFactory, $apiUrl );
		$api->get( $params );
	}

	public function testGetReturnsAdaptedResponse() {
		$headers = [ 'Some-header' => [ 'some value' ] ];
		$mwResponse = $this->newMockResponseWithHeaders( $headers );

		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $mwResponse );

		$api = new GenericActionApiClient( $requestFactory, 'https://does-not-matter/' );
		$response = $api->get( [] );

		$this->assertEquals( $headers, $response->getHeaders() );
	}

	private function newMockResponseWithHeaders( array $headers = [ 'Some-header' => [ 'some' ] ] ) {
		$mwResponse = $this->createMock( MWHttpRequest::class );
		$mwResponse->expects( $this->any() )
			->method( 'getResponseHeaders' )
			->willReturn( $headers );

		return $mwResponse;
	}

}
