<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @license GPL-2.0-or-later
 */
trait HttpResponseMockerTrait {

	/**
	 * Some test appear to want to simulate null status codes, hence the type hint
	 */
	private function newMockResponse( $response, ?int $statusCode ): ResponseInterface {
		$mwResponse = $this->createMock( ResponseInterface::class );
		$mwResponse->expects( $this->any() )
			->method( 'getStatusCode' )
			->willReturn( $statusCode );
		$mwResponse->expects( $this->any() )
			->method( 'getBody' )
			->willReturn( stream_for( $response ) );
		return $mwResponse;
	}

}
