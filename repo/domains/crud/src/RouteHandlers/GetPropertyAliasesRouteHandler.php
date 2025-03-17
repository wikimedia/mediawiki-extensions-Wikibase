<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\GetPropertyAliasesRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\GetPropertyAliasesResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';

	private GetPropertyAliases $useCase;
	private AliasesSerializer $aliasesSerializer;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetPropertyAliases $useCase,
		AliasesSerializer $aliasesSerializer,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		return new self(
			WbCrud::getGetPropertyAliases(),
			new AliasesSerializer(),
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::PROPERTY_ID_PATH_PARAM )
				),
			] ),
			new ResponseFactory()
		);
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
				$this->useCase->execute( new GetPropertyAliasesRequest( $propertyId ) )
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( GetPropertyAliasesResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody(
			new StringStream( json_encode( $this->aliasesSerializer->serialize( $useCaseResponse->getAliases() ) ) )
		);

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
	}

	public function needsWriteAccess(): bool {
		return false;
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

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

}
