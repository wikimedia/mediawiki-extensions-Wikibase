<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\GetPropertyLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\GetPropertyLabelResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;
use function json_encode;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';
	public const ROUTE = '/wikibase/v1/entities/properties/{property_id}/labels/{language_code}';

	private GetPropertyLabel $useCase;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetPropertyLabel $useCase,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		$responseFactory = new ResponseFactory();
		return new self(
			WbCrud::getGetPropertyLabel(),
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::PROPERTY_ID_PATH_PARAM )
				),
			] ),
			$responseFactory
		);
	}

	public function run( string $propertyId, string $languageCode ): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase( $propertyId, $languageCode ) );
	}

	public function runUseCase( string $propertyId, string $languageCode ): Response {
		try {
			$useCaseResponse = $this->useCase->execute( new GetPropertyLabelRequest( $propertyId, $languageCode ) );
			return $this->newSuccessResponse( $useCaseResponse );
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
			self::LANGUAGE_CODE_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	private function newSuccessResponse( GetPropertyLabelResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setBody(
			new StringStream( json_encode( $useCaseResponse->getLabel()->getText() ) )
		);

		return $httpResponse;
	}

	/**
	 * @inheritDoc
	 */
	public function needsWriteAccess(): bool {
		return false;
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

}
