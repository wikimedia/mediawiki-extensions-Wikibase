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
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\RequiredRequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementRouteHandler extends SimpleHandler {

	public const PROPERTY_ID_PATH_PARAM = 'property_id';
	public const STATEMENT_ID_PATH_PARAM = 'statement_id';
	public const ROUTE = '/wikibase/v0/entities/properties/{property_id}/statements/{statement_id}';

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
				new RequiredRequestedSubjectIdValidator( new PropertyIdValidator() )
			),
			WbRestApi::getSerializerFactory()->newStatementSerializer(),
			$responseFactory,
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::PROPERTY_ID_PATH_PARAM )
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

	public function runUseCase( string $propertyId, string $statementId ): Response {
		try {
			return $this->newSuccessHttpResponse(
				$this->getStatement->execute(
					new GetStatementRequest( $statementId, $propertyId )
				)
			);
		} catch ( UseCaseError $e ) {
			if ( $e->getErrorCode() === UseCaseError::STATEMENT_SUBJECT_NOT_FOUND ) {
				return $this->responseFactory->newErrorResponse(
					UseCaseError::PROPERTY_NOT_FOUND,
					"Could not find a property with the ID: {$e->getErrorContext()['subject-id']}"
				);
			}
			if ( $e->getErrorCode() === UseCaseError::INVALID_STATEMENT_SUBJECT_ID ) {
				return $this->responseFactory->newErrorResponse(
					UseCaseError::INVALID_PROPERTY_ID,
					"Not a valid property ID: {$e->getErrorContext()['subject-id']}",
					[ 'property-id' => $e->getErrorContext()['subject-id'] ]
				);
			}
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
