<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use MediaWiki\Rest\Validator\Validator;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\Presentation\Presenters\StatementJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const STATEMENT_BODY_PARAM = 'statement';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private $addItemStatement;
	private $successPresenter;
	private $responseFactory;
	private $middlewareHandler;

	public function __construct(
		AddItemStatement $addItemStatement,
		StatementJsonPresenter $successPresenter,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->addItemStatement = $addItemStatement;
		$this->successPresenter = $successPresenter;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory( new ErrorJsonPresenter() );
		return new self(
			WbRestApi::getAddItemStatement(),
			new StatementJsonPresenter( WbRestApi::getSerializerFactory()->newStatementSerializer() ),
			$responseFactory,
			new MiddlewareHandler( [
				new UnexpectedErrorHandlerMiddleware( $responseFactory, WikibaseRepo::getLogger() ),
			] )
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	/**
	 * @throws \Exception
	 */
	public function runUseCase( string $itemId ): Response {
		$jsonBody = $this->getValidatedBody();
		$useCaseResponse = $this->addItemStatement->execute(
			new AddItemStatementRequest(
				$itemId,
				$jsonBody[self::STATEMENT_BODY_PARAM],
				$jsonBody[self::TAGS_BODY_PARAM],
				$jsonBody[self::BOT_BODY_PARAM],
				$jsonBody[self::COMMENT_BODY_PARAM],
				$this->getUsername()
			)
		);

		if ( $useCaseResponse instanceof AddItemStatementSuccessResponse ) {
			$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse, $itemId );
		} elseif ( $useCaseResponse instanceof AddItemStatementErrorResponse ) {
			$httpResponse =
				$useCaseResponse->getCode() === ErrorResponse::PERMISSION_DENIED ?
					// respond with framework error, when user cannot edit Item
					$this->getResponseFactory()->createHttpError( 403, [ 'error' => 'rest-write-denied' ] ) :
					$this->responseFactory->newErrorResponse( $useCaseResponse );
		} else {
			throw new \LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		$this->addAuthHeaderIfAuthenticated( $httpResponse );

		return $httpResponse;
	}

	/**
	 * @inheritDoc
	 */
	public function validate( Validator $restValidator ) {
		$contentType = $this->getContentType( $this->getRequest() );
		if ( $contentType !== 'application/json' ) {
			throw new HttpException(
				"Unsupported Content-Type", 415, [ 'content_type' => $contentType ]
			);
		}

		parent::validate( $restValidator );
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
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
		return new TypeValidatingJsonBodyValidator( [
			self::STATEMENT_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'object',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::TAGS_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => []
			],
			self::BOT_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => false
			],
			self::COMMENT_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		] );
	}

	private function newSuccessHttpResponse( AddItemStatementSuccessResponse $useCaseResponse, string $itemId ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 201 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$this->setLocationHeader( $httpResponse, $itemId, $useCaseResponse->getStatement()->getGuid() );
		$httpResponse->setBody(
			new StringStream(
				$this->successPresenter->getJson( $useCaseResponse->getStatement() )
			)
		);

		return $httpResponse;
	}

	private function addAuthHeaderIfAuthenticated( Response $httpResponse ): void {
		$user = $this->getAuthority()->getUser();
		if ( $user->isRegistered() ) {
			$httpResponse->setHeader( 'X-Authenticated-User', $user->getName() );
		}
	}

	private function setEtagFromRevId( Response $httpResponse, int $revId ): void {
		$httpResponse->setHeader( 'ETag', "\"$revId\"" );
	}

	private function setLocationHeader( Response $httpResponse, string $itemId, string $statementGuid ): void {
		$newStatementUrl = $this->getRouter()->getRouteUrl(
			GetItemStatementRouteHandler::ROUTE,
			[
				GetItemStatementRouteHandler::ITEM_ID_PATH_PARAM => $itemId,
				GetItemStatementRouteHandler::STATEMENT_ID_PATH_PARAM => $statementGuid,
			]
		);

		$httpResponse->setHeader( 'Location', $newStatementUrl );
	}

	// use the helper method if Ie8650198c4afde4721da78ca506548f32732765d gets merged
	private function getContentType( RequestInterface $request ): string {
		list( $ct ) = explode( ';', $request->getHeaderLine( 'Content-Type' ), 2 );

		return strtolower( trim( $ct ) );
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
