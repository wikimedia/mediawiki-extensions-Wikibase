<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use Wikibase\Repo\RestApi\Presentation\ErrorResponseToHttpStatus;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandler {

	private $presenter;

	public function __construct( ErrorJsonPresenter $presenter ) {
		$this->presenter = $presenter;
	}

	/**
	 * @return mixed|Response
	 */
	public function runWithErrorHandling( callable $run, array $args ) {
		try {
			return $run( ...$args );
		} catch ( \Exception $exception ) {
			$error = new ErrorResponse( ErrorResponse::UNEXPECTED_ERROR, 'Unexpected error' );
			$response = new Response( $this->presenter->getJson( $error ) );
			$response->setStatus( ErrorResponseToHttpStatus::lookup( $error ) );
			$response->setHeader( 'Content-Type', 'application/json' );

			return $response;
		}
	}

}
