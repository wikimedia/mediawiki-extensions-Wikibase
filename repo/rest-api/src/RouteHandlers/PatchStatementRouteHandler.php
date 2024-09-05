<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\Validator;
use Wikibase\Repo\RestApi\Application\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\BotRightCheckMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheck;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchStatementRouteHandler extends SimpleHandler {

	use AssertValidTopLevelFields;

	private const STATEMENT_ID_PATH_PARAM = 'statement_id';
	private const PATCH_BODY_PARAM = 'patch';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private PatchStatement $useCase;
	private MiddlewareHandler $middlewareHandler;
	private StatementSerializer $statementSerializer;
	private ResponseFactory $responseFactory;

	public function __construct(
		PatchStatement $useCase,
		MiddlewareHandler $middlewareHandler,
		StatementSerializer $statementSerializer,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->middlewareHandler = $middlewareHandler;
		$this->statementSerializer = $statementSerializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();

		return new self(
			WbRestApi::getPatchStatement(),
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				new BotRightCheckMiddleware( MediaWikiServices::getInstance()->getPermissionManager(), $responseFactory ),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => RequestPreconditionCheck::getSubjectIdPrefixFromStatementId(
						$request->getPathParam( self::STATEMENT_ID_PATH_PARAM )
					)
				),
				WbRestApi::getStatementRedirectMiddlewareFactory()->newStatementRedirectMiddleware(
					self::STATEMENT_ID_PATH_PARAM
				),
			] ),
			WbRestApi::getStatementSerializer(),
			$responseFactory
		);
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $statementId ): Response {
		$requestBody = $this->getValidatedBody();
		'@phan-var array $requestBody'; // guaranteed to be an array per getBodyParamSettings()

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute(
					new PatchStatementRequest(
						$statementId,
						$requestBody[self::PATCH_BODY_PARAM],
						$requestBody[self::TAGS_BODY_PARAM],
						$requestBody[self::BOT_BODY_PARAM],
						$requestBody[self::COMMENT_BODY_PARAM],
						$this->getUsername()
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		} catch ( ItemRedirect $e ) {
			return $this->responseFactory
				->newErrorResponseFromException( UseCaseError::newResourceNotFound( 'statement' ) );
		}
	}

	public function getSupportedRequestTypes(): array {
		return [ 'application/json', 'application/json-patch+json' ];
	}

	public function validate( Validator $restValidator ): void {
		$this->assertValidTopLevelTypes( $this->getRequest()->getParsedBody(), $this->getBodyParamSettings() );
		parent::validate( $restValidator );
	}

	public function getParamSettings(): array {
		return [
			self::STATEMENT_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	public function getBodyParamSettings(): array {
		return [
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
			],
		];
	}

	private function newSuccessHttpResponse( PatchStatementResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setBody(
			new StringStream(
				json_encode(
					$this->statementSerializer->serialize( $useCaseResponse->getStatement() )
				)
			)
		);

		return $httpResponse;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
