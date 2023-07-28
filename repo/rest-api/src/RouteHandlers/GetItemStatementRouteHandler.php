<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\RequiredRequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const STATEMENT_ID_PATH_PARAM = 'statement_id';
	public const ROUTE = '/wikibase/v0/entities/items/{item_id}/statements/{statement_id}';

	private GetStatement $getStatement;
	private StatementSerializer $statementSerializer;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		GetStatement $getStatement,
		StatementSerializer $statementSerializer,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->getStatement = $getStatement;
		$this->statementSerializer = $statementSerializer;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();
		return new self(
			WbRestApi::getStatementFactory()->newGetStatement(
				new RequiredRequestedSubjectIdValidator( new ItemIdValidator() )
			),
			WbRestApi::getSerializerFactory()->newStatementSerializer(),
			$responseFactory,
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
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

	public function runUseCase( string $itemId, string $statementId ): Response {
		try {
			return $this->newSuccessHttpResponse(
				$this->getStatement->execute(
					new GetStatementRequest( $statementId, $itemId )
				)
			);
		} catch ( UseCaseError $e ) {
			if ( $e->getErrorCode() === UseCaseError::STATEMENT_SUBJECT_NOT_FOUND ) {
				return $this->responseFactory->newErrorResponse(
					UseCaseError::ITEM_NOT_FOUND,
					"Could not find an item with the ID: $itemId"
				);
			}
			if ( $e->getErrorCode() === UseCaseError::INVALID_STATEMENT_SUBJECT_ID ) {
				return $this->responseFactory->newErrorResponse(
					UseCaseError::INVALID_ITEM_ID,
					"Not a valid item ID: $itemId"
				);
			}
			return $this->responseFactory->newErrorResponseFromException( $e );
		} catch ( ItemRedirect $e ) {
			return $this->responseFactory->newErrorResponse(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: $statementId"
			);
		}
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

	private function newSuccessHttpResponse( GetStatementResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody(
			new StringStream(
				json_encode( $this->statementSerializer->serialize( $useCaseResponse->getStatement() ) )
			)
		);

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $httpResponse, int $revId ): void {
		$httpResponse->setHeader( 'ETag', "\"$revId\"" );
	}

}
