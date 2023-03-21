<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliasesInLanguageRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';

	private GetItemAliasesInLanguage $useCase;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetItemAliasesInLanguage $useCase,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		return new self(
			WbRestApi::getGetItemAliasesInLanguage(),
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
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
			return $this->newSuccessHttpResponse(
				$this->useCase->execute( new GetItemAliasesInLanguageRequest( $itemId, $languageCode ) )
			);
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

	private function newSuccessHttpResponse( GetItemAliasesInLanguageResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody(
			new StringStream( json_encode( $useCaseResponse->getAliasesInLanguage()->getAliases() ) )
		);

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
	 *
	 * @inheritDoc
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

}
