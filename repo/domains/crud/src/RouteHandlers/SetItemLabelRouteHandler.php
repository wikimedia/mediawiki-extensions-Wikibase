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
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\SetItemLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\SetItemLabelResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\Middleware\CaptchaMiddleware;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\EditMetadataRequestParams;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use Wikimedia\Timestamp\TimestampFormat as TS;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabelRouteHandler extends SimpleHandler {
	use AssertValidTopLevelFields;
	use EditMetadataRequestParams;

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';
	private const LABEL_BODY_PARAM = 'label';

	private SetItemLabel $setItemLabel;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public function __construct( SetItemLabel $setItemLabel, MiddlewareHandler $middlewareHandler, ResponseFactory $responseFactory ) {
		$this->setItemLabel = $setItemLabel;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();

		return new self(
			WbCrud::getSetItemLabel(),
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new CaptchaMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
				),
				new TempUserCreationResponseHeaderMiddleware( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) ),
			] ),
			$responseFactory
		);
	}

	public function run( string $itemId, string $languageCode ): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase( $itemId, $languageCode ) );
	}

	public function runUseCase( string $itemId, string $languageCode ): Response {
		$jsonBody = $this->getValidatedBodyArray();
		try {
			$useCaseResponse = $this->setItemLabel->execute(
				new SetItemLabelRequest(
					$itemId,
					$languageCode,
					$jsonBody[self::LABEL_BODY_PARAM],
					$jsonBody[self::TAGS_BODY_PARAM],
					$jsonBody[self::BOT_BODY_PARAM],
					$jsonBody[self::COMMENT_BODY_PARAM],
					$this->getUsername()
				)
			);
			return $this->newSuccessHttpResponse( $useCaseResponse );
		} catch ( ItemRedirect $e ) {
			return $this->responseFactory->newErrorResponse(
				UseCaseError::RESOURCE_REDIRECTED,
				"Resource $itemId has been redirected to {$e->getRedirectTargetId()}.",
				[ UseCaseError::CONTEXT_REDIRECT_TARGET => $e->getRedirectTargetId() ]
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	public function validate( Validator $restValidator ): void {
		$this->assertValidTopLevelTypes( $this->getRequest()->getParsedBody(), $this->getBodyParamSettings() );
		parent::validate( $restValidator );
	}

	/**
	 * @inheritDoc
	 */
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

	public function getBodyParamSettings(): array {
		return [
			self::LABEL_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
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

	private function newSuccessHttpResponse( SetItemLabelResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( $useCaseResponse->wasReplaced() ? 200 : 201 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader(
			'Last-Modified',
			ConvertibleTimestamp::convert( TS::RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setBody( new StringStream( json_encode( $useCaseResponse->getLabel()->getText() ) ) );

		return $httpResponse;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
