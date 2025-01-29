<?php declare( strict_types = 1 );

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
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelinkRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelinkResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\BotRightCheckMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\Domains\Crud\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SetSitelinkRouteHandler extends SimpleHandler {

	use AssertValidTopLevelFields;

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const SITE_ID_PATH_PARAM = 'site_id';
	private const SITELINK_BODY_PARAM = 'sitelink';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private SetSitelink $useCase;
	private SiteLinkSerializer $sitelinkSerializer;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		SetSitelink $useCase,
		ResponseFactory $responseFactory,
		SiteLinkSerializer $sitelinkSerializer,
		MiddlewareHandler $middlewareHandler
	) {
		$this->useCase = $useCase;
		$this->sitelinkSerializer = $sitelinkSerializer;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();

		return new self(
			WbRestApi::getSetSitelink(),
			$responseFactory,
			new SitelinkSerializer(),
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				new BotRightCheckMiddleware( MediaWikiServices::getInstance()->getPermissionManager(), $responseFactory ),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
				),
				new TempUserCreationResponseHeaderMiddleware( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) ),
			] )
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $itemId, string $siteId ): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute(
					new SetSitelinkRequest(
						$itemId,
						$siteId,
						$jsonBody['sitelink'],
						$jsonBody['tags'],
						$jsonBody['bot'],
						$jsonBody['comment'],
						$this->getUsername()
					)
				)
			);
		} catch ( ItemRedirect $e ) {
			return $this->responseFactory->newErrorResponse(
				UseCaseError::ITEM_REDIRECTED,
				"Item $itemId has been redirected to {$e->getRedirectTargetId()}.",
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

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::SITE_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	public function getBodyParamSettings(): array {
		return [
			self::SITELINK_BODY_PARAM => [
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
			],
		];
	}

	private function newSuccessHttpResponse( SetSitelinkResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( $useCaseResponse->wasReplaced() ? 200 : 201 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$httpResponse->setBody(
			new StringStream(
				json_encode(
					$this->sitelinkSerializer->serialize( $useCaseResponse->getSiteLink() ),
					JSON_UNESCAPED_SLASHES
				)
			)
		);

		return $httpResponse;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}
}
