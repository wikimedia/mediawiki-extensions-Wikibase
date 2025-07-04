<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ResponseFactory {

	public function newSuccessResponse( array $responseBody ): Response {
		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody(
			new StringStream(
				json_encode( $responseBody, JSON_UNESCAPED_SLASHES )
			)
		);

		return $httpResponse;
	}

	public function newUseCaseErrorResponse( UseCaseError $e ): Response {
		return $this->newErrorResponse(
			ErrorResponseToHttpStatus::lookup( $e->getErrorCode() ),
			$e->getErrorCode(),
			$e->getErrorMessage(),
			$e->getErrorContext()
		);
	}

	public function newErrorResponse( int $statusCode, string $code, string $message, ?array $context = null ): Response {
		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setStatus( $statusCode );
		$httpResponse->setBody( new StringStream( json_encode(
			// use array_filter to remove 'context' from array if $context is NULL
			array_filter( [ 'code' => $code, 'message' => $message, 'context' => $context ] ),
			JSON_UNESCAPED_SLASHES
		) ) );

		return $httpResponse;
	}

}
