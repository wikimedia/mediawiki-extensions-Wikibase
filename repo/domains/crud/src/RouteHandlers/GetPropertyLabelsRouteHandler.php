<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\GetPropertyLabelsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\GetPropertyLabelsResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\Domains\Crud\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelsRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';

	private GetPropertyLabels $useCase;
	private LabelsSerializer $labelsSerializer;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetPropertyLabels $useCase,
		LabelsSerializer $labelsSerializer,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->labelsSerializer = $labelsSerializer;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		$responseFactory = new ResponseFactory();
		return new self(
			WbRestApi::getGetPropertyLabels(),
			new LabelsSerializer(),
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::PROPERTY_ID_PATH_PARAM )
				),
			] ),
			$responseFactory
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

	public function runUseCase( string $propertyId ): Response {
		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute( new GetPropertyLabelsRequest( $propertyId ) )
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	public function getParamSettings(): array {
		return [
			self::PROPERTY_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	private function newSuccessHttpResponse( GetPropertyLabelsResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream( json_encode( $this->labelsSerializer->serialize( $useCaseResponse->getLabels() ) ) ) );

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

}
