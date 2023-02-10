<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use HttpStatus;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\ErrorResponseToHttpStatus;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

/**
 * @license GPL-2.0-or-later
 */
class ResponseFactory {

	private ErrorJsonPresenter $errorPresenter;

	public function __construct( ErrorJsonPresenter $errorPresenter ) {
		$this->errorPresenter = $errorPresenter;
	}

	public function newErrorResponseFromException( UseCaseException $e ): Response {
		// quick hack for now while we test UseCaseException vs ErrorResponse
		return $this->newErrorResponse(
			new ErrorResponse( $e->getErrorCode(), $e->getErrorMessage(), $e->getErrorContext() )
		);
	}

	public function newErrorResponse( ErrorResponse $useCaseResponse ): Response {
		// respond with framework error, when user cannot edit the Item
		if ( $useCaseResponse->getCode() === ErrorResponse::PERMISSION_DENIED ) {
			return $this->newFrameworkAlikePermissionDeniedResponse();
		}

		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setStatus( ErrorResponseToHttpStatus::lookup( $useCaseResponse ) );
		$httpResponse->setBody( new StringStream( $this->errorPresenter->getJson( $useCaseResponse ) ) );

		return $httpResponse;
	}

	private function newFrameworkAlikePermissionDeniedResponse(): Response {
		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setStatus( 403 );
		$httpResponse->setBody( new StringStream( json_encode( [
			'error' => 'rest-write-denied',
			'httpCode' => 403,
			'httpReason' => HttpStatus::getMessage( 403 ),
		] ) ) );

		return $httpResponse;
	}

}
