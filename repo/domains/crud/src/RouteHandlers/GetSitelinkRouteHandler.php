<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\GetSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\GetSitelinkRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\GetSitelinkResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use Wikimedia\Timestamp\TimestampFormat as TS;

/**
 * @license GPL-2.0-or-later
 */
class GetSitelinkRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const SITE_ID_PATH_PARAM = 'site_id';

	private GetSitelink $useCase;
	private SitelinkSerializer $sitelinkSerializer;
	private MiddlewareHandler $middlewareHandler;
	private ResponseFactory $responseFactory;

	public static function factory(): self {
		return new GetSitelinkRouteHandler(
			WbCrud::getGetSitelink(),
			new SitelinkSerializer(),
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
				),
			] ),
			new ResponseFactory()
		);
	}

	public function __construct(
		GetSitelink $useCase,
		SitelinkSerializer $sitelinkSerializer,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->sitelinkSerializer = $sitelinkSerializer;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	public function run( string $itemId, string $siteId ): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase( $itemId, $siteId ) );
	}

	public function runUseCase( string $itemId, string $siteId ): Response {
		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute( new GetSitelinkRequest( $itemId, $siteId ) )
			);
		} catch ( ItemRedirect $redirect ) {
			return $this->newRedirectHttpResponse( $redirect, $siteId );
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
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

	private function newSuccessHttpResponse( GetSitelinkResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', ConvertibleTimestamp::convert( TS::RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );

		$httpResponse->setBody(
			new StringStream(
				json_encode( $this->sitelinkSerializer->serialize( $useCaseResponse->getSitelink() ), JSON_UNESCAPED_SLASHES )
			)
		);

		return $httpResponse;
	}

	private function newRedirectHttpResponse( ItemRedirect $redirect, string $siteId ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl( [
				self::ITEM_ID_PATH_PARAM => $redirect->getRedirectTargetId(),
				self::SITE_ID_PATH_PARAM => $siteId,
			] )
		);
		$httpResponse->setStatus( 308 );

		return $httpResponse;
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}
}
