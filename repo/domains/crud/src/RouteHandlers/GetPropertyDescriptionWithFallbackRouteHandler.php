<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallbackRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallbackResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\Domains\Crud\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionWithFallbackRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';

	private GetPropertyDescriptionWithFallback $useCase;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetPropertyDescriptionWithFallback $useCase,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		return new self(
			WbRestApi::getGetPropertyDescriptionWithFallback(),
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::PROPERTY_ID_PATH_PARAM )
				),
			] ),
			new ResponseFactory()
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

	public function runUseCase( string $propertyId, string $languageCode ): Response {
		try {
			$response = $this->useCase->execute( new GetPropertyDescriptionWithFallbackRequest( $propertyId, $languageCode ) );

			return $response->getDescription()->getLanguageCode() === $languageCode
				? $this->newSuccessHttpResponse( $response )
				: $this->newLanguageFallbackResponse( $propertyId, $response->getDescription()->getLanguageCode() );
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

	private function newSuccessHttpResponse( GetPropertyDescriptionWithFallbackResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream( json_encode( $useCaseResponse->getDescription()->getText() ) ) );

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

	private function newLanguageFallbackResponse( string $propertyId, string $fallbackLanguageCode ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouter()->getRouteUrl(
				GetPropertyDescriptionRouteHandler::ROUTE,
				[
					self::PROPERTY_ID_PATH_PARAM => $propertyId,
					self::LANGUAGE_CODE_PATH_PARAM => $fallbackLanguageCode,
				]
			)
		);
		$httpResponse->setStatus( 307 );

		return $httpResponse;
	}

}
