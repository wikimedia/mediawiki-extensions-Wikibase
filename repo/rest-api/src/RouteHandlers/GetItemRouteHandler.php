<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use LogicException;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\Serialization\ItemDataSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRouteHandler extends SimpleHandler {
	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const FIELDS_QUERY_PARAM = '_fields';

	private GetItem $getItem;

	private ItemDataSerializer $itemDataSerializer;

	private ResponseFactory $responseFactory;

	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		GetItem $getItem,
		ItemDataSerializer $itemDataSerializer,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->getItem = $getItem;
		$this->itemDataSerializer = $itemDataSerializer;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory( new ErrorJsonPresenter() );
		return new self(
			WbRestApi::getGetItem(),
			WbRestApi::getSerializerFactory()->newItemDataSerializer(),
			$responseFactory,
			new MiddlewareHandler( [
				new UnexpectedErrorHandlerMiddleware( $responseFactory, WikibaseRepo::getLogger() ),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				WbRestApi::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					function ( RequestInterface $request ): string {
							return $request->getPathParam( self::ITEM_ID_PATH_PARAM );
					}
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

	public function runUseCase( string $id ): Response {
		$fields = explode( ',', $this->getValidatedParams()[self::FIELDS_QUERY_PARAM] );
		$useCaseResponse = $this->getItem->execute( new GetItemRequest( $id, $fields ) );

		if ( $useCaseResponse instanceof GetItemSuccessResponse ) {
			$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof ItemRedirectResponse ) {
			$httpResponse = $this->newRedirectHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof GetItemErrorResponse ) {
			$httpResponse = $this->responseFactory->newErrorResponse( $useCaseResponse );
		} else {
			throw new LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		return $httpResponse;
	}

	private function newSuccessHttpResponse( GetItemSuccessResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream(
			json_encode( $this->itemDataSerializer->serialize( $useCaseResponse->getItemData() ) )
		) );

		return $httpResponse;
	}

	private function newRedirectHttpResponse( ItemRedirectResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl(
				[ self::ITEM_ID_PATH_PARAM => $useCaseResponse->getRedirectTargetId() ],
				$this->getRequest()->getQueryParams()
			)
		);
		$httpResponse->setStatus( 308 );

		return $httpResponse;
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
			self::FIELDS_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => implode( ',', ItemData::VALID_FIELDS ),
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

}
