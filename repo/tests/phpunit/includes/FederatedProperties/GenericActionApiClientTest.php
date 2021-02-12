<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use PHPUnit\Framework\TestCase;
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
class GenericActionApiClientTest extends TestCase {

	/**
	 * @var string
	 */
	private $serverName = 'this.needs.to.be.static';

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
			new NullLogger(),
			$this->serverName
		);
		$api->get( $params );
	}

	public function testGetReturnsAdaptedResponse() {
		$headers = [ 'Some-header' => [ 'some value' ] ];
		$mwResponse = $this->newMockResponseWithHeaders( $headers );

		$url = 'https://does-not-matter/';
		$api = new GenericActionApiClient(
			$this->newMockRequestFactory( $mwResponse, $url ),
			$url,
			new NullLogger(),
			$this->serverName
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
			$logger,
			$this->serverName
		);
		$api->get( $params );

		$logger->hasDebugThatContains( 'https://wikidata.org/w/api.php' );
	}

	public function testGivenRequestHitsTimeout_throwsException() {
		$url = 'https://wikidata.org/w/api.php';
		$response = $this->newMockResponseWithHeaders();
		$response->expects( $this->once() )
			->method( 'getStatus' )
			->willReturn( 0 );
		$api = new GenericActionApiClient(
			$this->newMockRequestFactory( $response, $url ),
			$url,
			new NullLogger(),
			$this->serverName
		);

		$this->expectException( ApiRequestExecutionException::class );
		$api->get( [] );
	}

	private function newMockResponseWithHeaders( array $headers = [ 'Some-header' => [ 'some' ] ] ) {
		$mwResponse = $this->createMock( MWHttpRequest::class );
		$mwResponse->method( 'getResponseHeaders' )
			->willReturn( $headers );

		return $mwResponse;
	}

	private function newMockRequestFactory( $mwResponse, $url ) {
		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->method( 'getUserAgent' )
			->withAnyParameters()
			->willReturn( 'MediaWiki/N.NN.N-test' );
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->with(
				$url,
				[ 'userAgent' => 'MediaWiki/N.NN.N-test Wikibase-FederatedProperties (0b2eec0d3e5fe90a000893458285ab32)' ],
				'Wikibase\Repo\FederatedProperties\GenericActionApiClient::get'
			)
			->willReturn( $mwResponse );

		return $requestFactory;
	}

}
