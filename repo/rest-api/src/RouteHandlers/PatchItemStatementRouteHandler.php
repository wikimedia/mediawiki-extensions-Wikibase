<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\Presentation\Presenters\StatementJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\ContentTypeCheckMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const STATEMENT_ID_PATH_PARAM = 'statement_id';
	public const PATCH_BODY_PARAM = 'patch';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private $useCase;
	private $middlewareHandler;
	private $successPresenter;
	private $responseFactory;

	public function __construct(
		PatchItemStatement $useCase,
		MiddlewareHandler $middlewareHandler,
		StatementJsonPresenter $successPresenter,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->middlewareHandler = $middlewareHandler;
		$this->successPresenter = $successPresenter;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		return new self(
			WbRestApi::getPatchItemStatement(),
			new MiddlewareHandler( [
				new UnexpectedErrorHandlerMiddleware( new ResponseFactory( new ErrorJsonPresenter() ), WikibaseRepo::getLogger() ),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				new ContentTypeCheckMiddleware( [
					ContentTypeCheckMiddleware::TYPE_APPLICATION_JSON,
					ContentTypeCheckMiddleware::TYPE_JSON_PATCH,
				] ),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					function ( RequestInterface $request ): string {
						return $request->getPathParam( self::ITEM_ID_PATH_PARAM );
					}
				),
			] ),
			new StatementJsonPresenter( WbRestApi::getSerializerFactory()->newStatementSerializer() ),
			new ResponseFactory( new ErrorJsonPresenter() )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function checkPreconditions() {
		return null; // preconditions are checked via ModifiedPreconditionMiddleware
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $itemId, string $statementId ): Response {
		$requestBody = $this->getValidatedBody();

		$useCaseResponse = $this->useCase->execute( new PatchItemStatementRequest(
			$statementId,
			$requestBody[self::PATCH_BODY_PARAM],
			$requestBody[self::TAGS_BODY_PARAM],
			$requestBody[self::BOT_BODY_PARAM],
			$requestBody[self::COMMENT_BODY_PARAM],
			$this->getUsername(),
			$itemId
		) );

		if ( $useCaseResponse instanceof PatchItemStatementSuccessResponse ) {
			$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof PatchItemStatementErrorResponse ) {
			$httpResponse = $this->responseFactory->newErrorResponse( $useCaseResponse );
		} else {
			throw new \LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		return $httpResponse;
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::STATEMENT_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return $contentType === 'application/json' || $contentType === 'application/json-patch+json' ?
			new TypeValidatingJsonBodyValidator( [
				self::PATCH_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'array',
					ParamValidator::PARAM_REQUIRED => true,
				],
				self::TAGS_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'array',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => [],
				],
				self::BOT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'boolean',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => false,
				],
				self::COMMENT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => null,
				]
			] ) : parent::getBodyValidator( $contentType );
	}

	private function newSuccessHttpResponse( PatchItemStatementSuccessResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setBody( new StringStream(
			$this->successPresenter->getJson( $useCaseResponse->getStatement() )
		) );

		return $httpResponse;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
