<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallbackRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallbackResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionWithFallbackRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';

	private GetItemDescriptionWithFallback $useCase;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetItemDescriptionWithFallback $useCase,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		return new self(
			WbCrud::getGetItemDescriptionWithFallback(),
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
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

	public function runUseCase( string $itemId, string $languageCode ): Response {
		try {
			$response = $this->useCase->execute( new GetItemDescriptionWithFallbackRequest( $itemId, $languageCode ) );

			return $response->getDescription()->getLanguageCode() === $languageCode
				? $this->newSuccessHttpResponse( $response )
				: $this->newLanguageFallbackResponse( $itemId, $response->getDescription()->getLanguageCode() );
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		} catch ( ItemRedirect $redirect ) {
			return $this->newRedirectHttpResponse( $redirect, $languageCode );
		}
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
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

	private function newSuccessHttpResponse( GetItemDescriptionWithFallbackResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody(
			new StringStream( json_encode( $useCaseResponse->getDescription()->getText() ) )
		);

		return $httpResponse;
	}

	private function newLanguageFallbackResponse( string $itemId, string $fallbackLanguageCode ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouter()->getRouteUrl(
				GetItemDescriptionRouteHandler::ROUTE,
				[
					self::ITEM_ID_PATH_PARAM => $itemId,
					self::LANGUAGE_CODE_PATH_PARAM => $fallbackLanguageCode,
				]
			)
		);
		$httpResponse->setStatus( 307 );

		return $httpResponse;
	}

	private function newRedirectHttpResponse( ItemRedirect $e, string $languageCode ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl( [
				self::ITEM_ID_PATH_PARAM => $e->getRedirectTargetId(),
				self::LANGUAGE_CODE_PATH_PARAM => $languageCode,
			] )
		);
		$httpResponse->setStatus( 308 );
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
