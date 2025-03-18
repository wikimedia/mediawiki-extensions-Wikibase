<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement\GetItemStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\GetStatementResponse;
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
class GetItemStatementRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const STATEMENT_ID_PATH_PARAM = 'statement_id';
	public const ROUTE = '/wikibase/v1/entities/items/{item_id}/statements/{statement_id}';

	private GetItemStatement $getStatement;
	private StatementSerializer $statementSerializer;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		GetItemStatement $getStatement,
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
			WbCrud::getGetItemStatement(),
			WbCrud::getStatementSerializer(),
			$responseFactory,
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
				),
				WbCrud::getStatementRedirectMiddlewareFactory()->newStatementRedirectMiddleware(
					self::STATEMENT_ID_PATH_PARAM,
					self::ITEM_ID_PATH_PARAM
				),
			] )
		);
	}

	public function run( string $itemId, string $statementId ): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase( $itemId, $statementId ) );
	}

	public function runUseCase( string $itemId, string $statementId ): Response {
		try {
			return $this->newSuccessHttpResponse(
				$this->getStatement->execute(
					new GetItemStatementRequest( $itemId, $statementId )
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		} catch ( ItemRedirect $e ) {
			return $this->responseFactory
				->newErrorResponseFromException( UseCaseError::newResourceNotFound( 'statement' ) );
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
