<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\ErrorResponseToHttpStatus;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class ResponseFactory {

	private $errorPresenter;

	public function __construct( ErrorJsonPresenter $errorPresenter ) {
		$this->errorPresenter = $errorPresenter;
	}

	public function newErrorResponse( ErrorResponse $useCaseResponse ): Response {
		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setStatus( ErrorResponseToHttpStatus::lookup( $useCaseResponse ) );
		$httpResponse->setBody( new StringStream( $this->errorPresenter->getJson( $useCaseResponse ) ) );

		return $httpResponse;
	}
}
