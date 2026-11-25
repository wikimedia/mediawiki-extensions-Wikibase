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
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyLabel\RemovePropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyLabel\RemovePropertyLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\Middleware\CaptchaMiddleware;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\EditMetadataRequestParams;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class RemovePropertyLabelRouteHandler extends SimpleHandler {

	use AssertValidTopLevelFields;
	use EditMetadataRequestParams;

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';

	private RemovePropertyLabel $removePropertyLabel;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public function __construct(
		RemovePropertyLabel $removePropertyLabel,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->removePropertyLabel = $removePropertyLabel;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();
		return new self(
			WbCrud::getRemovePropertyLabel(),
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new CaptchaMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::PROPERTY_ID_PATH_PARAM )
				),
				new TempUserCreationResponseHeaderMiddleware( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) ),
			] ),
			$responseFactory
		);
	}

	public function run( string $propertyId, string $languageCode ): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase( $propertyId, $languageCode ) );
	}

	public function runUseCase( string $propertyId, string $languageCode ): Response {
		$requestBody = $this->getValidatedBodyArray();

		try {
			$this->removePropertyLabel->execute(
				new RemovePropertyLabelRequest(
					$propertyId,
					$languageCode,
					$requestBody[ self::TAGS_BODY_PARAM ],
					$requestBody[ self::BOT_BODY_PARAM ],
					$requestBody[ self::COMMENT_BODY_PARAM ],
					$this->getUsername()
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}

		return $this->newSuccessHttpResponse();
	}

	private function newSuccessHttpResponse(): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setBody( new StringStream( '"Label deleted"' ) );

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
			self::LANGUAGE_CODE_PATH_PARAM => [
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

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
