<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\RestApi\UseCases\GetItemDescriptions\GetItemDescriptionsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemDescriptions\GetItemDescriptionsResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectException;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionsRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';

	private GetItemDescriptions $useCase;
	private DescriptionsSerializer $descriptionsSerializer;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		GetItemDescriptions $useCase,
		DescriptionsSerializer $descriptionsSerializer,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->useCase = $useCase;
		$this->descriptionsSerializer = $descriptionsSerializer;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): self {
		return new self(
			WbRestApi::getGetItemDescriptions(),
			new DescriptionsSerializer(),
			new ResponseFactory( new ErrorJsonPresenter() ),
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
				),
			] ),
		);
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $itemId ): Response {
		try {
			$useCaseResponse = $this->useCase->execute( new GetItemDescriptionsRequest( $itemId ) );
			 return $this->newSuccessHttpResponse( $useCaseResponse );
		} catch ( ItemRedirectException $e ) {
			 return $this->newRedirectHttpResponse( $e );
		} catch ( UseCaseException $e ) {
			 return $this->responseFactory->newErrorResponseFromException( $e );
		}
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

	private function newSuccessHttpResponse( GetItemDescriptionsResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody(
			new StringStream( json_encode( $this->descriptionsSerializer->serialize( $useCaseResponse->getDescriptions() ) ) )
		);

		return $httpResponse;
	}

	private function newRedirectHttpResponse( ItemRedirectException $e ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl( [ self::ITEM_ID_PATH_PARAM => $e->getRedirectTargetId() ] )
		);
		$httpResponse->setStatus( 308 );

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
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
