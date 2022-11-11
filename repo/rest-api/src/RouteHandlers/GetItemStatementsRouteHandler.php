<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementsJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsRouteHandler extends SimpleHandler {
	private const ITEM_ID_PATH_PARAM = 'item_id';

	private GetItemStatements $getItemStatements;

	private GetItemStatementsJsonPresenter $successPresenter;

	private ResponseFactory $responseFactory;

	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		GetItemStatements $getItemStatements,
		GetItemStatementsJsonPresenter $presenter,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->getItemStatements = $getItemStatements;
		$this->successPresenter = $presenter;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory( new ErrorJsonPresenter() );
		return new self(
			WbRestApi::getGetItemStatements(),
			new GetItemStatementsJsonPresenter( WbRestApi::getDomainSerializerFactory()->newStatementListSerializer() ),
			$responseFactory,
			new MiddlewareHandler( [
				new UnexpectedErrorHandlerMiddleware( $responseFactory, WikibaseRepo::getLogger() ),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					function ( RequestInterface $request ): string {
						return $request->getPathParam( self::ITEM_ID_PATH_PARAM );
					}
				),
			] )
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $itemId ): Response {
		$useCaseResponse = $this->getItemStatements->execute( new GetItemStatementsRequest( $itemId ) );

		if ( $useCaseResponse instanceof GetItemStatementsSuccessResponse ) {
			$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof ItemRedirectResponse ) {
			$httpResponse = $this->newRedirectHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof GetItemStatementsErrorResponse ) {
			$httpResponse = $this->responseFactory->newErrorResponse( $useCaseResponse );
		} else {
			throw new \LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		return $httpResponse;
	}

	private function newSuccessHttpResponse( GetItemStatementsSuccessResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream( $this->successPresenter->getJson( $useCaseResponse ) ) );

		return $httpResponse;
	}

	private function newRedirectHttpResponse( ItemRedirectResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl( [ self::ITEM_ID_PATH_PARAM => $useCaseResponse->getRedirectTargetId() ] )
		);
		$httpResponse->setStatus( 308 );

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 *
	 * @inheritDoc
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

}
