<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\HookContainer\HookRunner;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\Validator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\Middleware\CaptchaMiddleware;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\RequestPreconditionCheck;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\EditMetadataRequestParams;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class RemoveStatementRouteHandler extends SimpleHandler {
	use AssertValidTopLevelFields;
	use EditMetadataRequestParams;

	private const STATEMENT_ID_PATH_PARAM = 'statement_id';

	private RemoveStatement $removeStatement;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		RemoveStatement $removeStatement,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->removeStatement = $removeStatement;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();
		return new self(
			WbCrud::getRemoveStatement(),
			new ResponseFactory(),
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new CaptchaMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => RequestPreconditionCheck::getSubjectIdPrefixFromStatementId(
						$request->getPathParam( self::STATEMENT_ID_PATH_PARAM )
					)
				),
				WbCrud::getStatementRedirectMiddlewareFactory()->newStatementRedirectMiddleware(
					self::STATEMENT_ID_PATH_PARAM
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

	public function run( string $statementId ): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase( $statementId ) );
	}

	public function runUseCase( string $statementId ): Response {
		$requestBody = $this->getValidatedBodyArray();
		try {
			$this->removeStatement->execute(
				new RemoveStatementRequest(
					$statementId,
					$requestBody[self::TAGS_BODY_PARAM],
					$requestBody[self::BOT_BODY_PARAM],
					$requestBody[self::COMMENT_BODY_PARAM],
					$this->getUsername()
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		} catch ( ItemRedirect ) {
			return $this->responseFactory
				->newErrorResponseFromException( UseCaseError::newResourceNotFound( 'statement' ) );
		}

		return $this->newSuccessHttpResponse();
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
			...self::getEditMetadataBodyParamSettings(),
			...CaptchaMiddleware::getBodyParamSettings(),
		];
	}

	private function newSuccessHttpResponse(): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setBody( new StringStream( '"Statement deleted"' ) );

		return $httpResponse;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
