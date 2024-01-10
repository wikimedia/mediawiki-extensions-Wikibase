<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\SiteLinkSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SiteLinksSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLinks\GetItemSiteLinks;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLinks\GetItemSiteLinksRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLinks\GetItemSiteLinksResponse;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinksRouteHandler extends SimpleHandler {
	private const ITEM_ID_PATH_PARAM = 'item_id';

	private GetItemSiteLinks $getItemSiteLinks;
	private SiteLinksSerializer $siteLinksSerializer;
	private MiddlewareHandler $middlewareHandler;

	private ResponseFactory $responseFactory;

	public function __construct(
		GetItemSiteLinks $getItemSiteLinks,
		SiteLinksSerializer $siteLinksSerializer,
		MiddlewareHandler $middlewareHandler,
		ResponseFactory $responseFactory
	) {
		$this->getItemSiteLinks = $getItemSiteLinks;
		$this->siteLinksSerializer = $siteLinksSerializer;
		$this->middlewareHandler = $middlewareHandler;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		return new self(
			WbRestApi::getGetItemSiteLinks(),
			new SiteLinksSerializer( new SiteLinkSerializer() ),
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
				),
			] ),
			new ResponseFactory()
		);
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $id ): Response {
		try {
			return $this->newSuccessHttpResponse(
				$this->getItemSiteLinks->execute( new GetItemSiteLinksRequest( $id ) )
			);
		} catch ( ItemRedirect $e ) {
			return $this->newRedirectHttpResponse( $e );
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
		];
	}

	private function newSuccessHttpResponse( GetItemSiteLinksResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream(
			json_encode( $this->siteLinksSerializer->serialize( $useCaseResponse->getSiteLinks() ), JSON_UNESCAPED_SLASHES )
		) );

		return $httpResponse;
	}

	private function newRedirectHttpResponse( ItemRedirect $e ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl( [ self::ITEM_ID_PATH_PARAM => $e->getRedirectTargetId() ] )
		);
		$httpResponse->setStatus( 308 );

		return $httpResponse;
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 *
	 * @inheritDoc
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}
}
