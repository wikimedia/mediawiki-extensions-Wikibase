<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\HookContainer\HookRunner;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\Validator;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\AddPropertyStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\AddPropertyStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\BotRightCheckMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use Wikimedia\Timestamp\TimestampFormat as TS;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementRouteHandler extends SimpleHandler {

	use AssertValidTopLevelFields;

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const STATEMENT_BODY_PARAM = 'statement';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private AddPropertyStatement $useCase;
	private StatementSerializer $statementSerializer;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		AddPropertyStatement $useCase,
		StatementSerializer $statementSerializer,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->useCase = $useCase;
		$this->statementSerializer = $statementSerializer;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): self {
		$responseFactory = new ResponseFactory();
		return new self(
			WbCrud::getAddPropertyStatement(),
			WbCrud::getStatementSerializer(),
			$responseFactory,
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				new BotRightCheckMiddleware( MediaWikiServices::getInstance()->getPermissionManager(), $responseFactory ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::PROPERTY_ID_PATH_PARAM )
				),
				new TempUserCreationResponseHeaderMiddleware( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) ),
			] )
		);
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

	public function run( string $propertyId ): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase( $propertyId ) );
	}

	public function runUseCase( string $propertyId ): Response {
		$body = $this->getValidatedBody();
		'@phan-var array $body'; // guaranteed to be an array per getBodyParamSettings()
		try {
			return $this->newSuccessHttpResponse(
				$propertyId,
				$this->useCase->execute(
					new AddPropertyStatementRequest(
						$propertyId,
						$body[self::STATEMENT_BODY_PARAM],
						$body[self::TAGS_BODY_PARAM],
						$body[self::BOT_BODY_PARAM],
						$body[self::COMMENT_BODY_PARAM],
						$this->getUsername()
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( string $propertyId, AddPropertyStatementResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 201 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			ConvertibleTimestamp::convert( TS::RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$statement = $useCaseResponse->getStatement();
		$this->setLocationHeader( $httpResponse, $propertyId, "{$statement->getGuid()}" );
		$httpResponse->setBody( new StringStream( json_encode(
			$this->statementSerializer->serialize( $statement )
		) ) );

		return $httpResponse;
	}

	public function validate( Validator $restValidator ): void {
		$this->assertValidTopLevelTypes( $this->getRequest()->getParsedBody(), $this->getBodyParamSettings() );
		parent::validate( $restValidator );
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

	public function getBodyParamSettings(): array {
		return [
			self::STATEMENT_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => /* object */ 'array',
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
			],
		];
	}

	private function setLocationHeader( Response $httpResponse, string $propertyId, string $statementGuid ): void {
		$newStatementUrl = $this->getRouter()->getRouteUrl(
			GetPropertyStatementRouteHandler::ROUTE,
			[
				GetPropertyStatementRouteHandler::PROPERTY_ID_PATH_PARAM => $propertyId,
				GetPropertyStatementRouteHandler::STATEMENT_ID_PATH_PARAM => $statementGuid,
			]
		);

		$httpResponse->setHeader( 'Location', $newStatementUrl );
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
