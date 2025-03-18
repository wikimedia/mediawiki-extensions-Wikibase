<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ItemPartsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\GetItemResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemParts;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRouteHandler extends SimpleHandler {

	public const ROUTE = '/wikibase/v1/entities/items/{item_id}';
	public const ITEM_ID_PATH_PARAM = 'item_id';
	private const FIELDS_QUERY_PARAM = '_fields';

	private GetItem $getItem;

	private ItemPartsSerializer $itemPartsSerializer;

	private ResponseFactory $responseFactory;

	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		GetItem $getItem,
		ItemPartsSerializer $itemPartsSerializer,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->getItem = $getItem;
		$this->itemPartsSerializer = $itemPartsSerializer;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();
		return new self(
			WbCrud::getGetItem(),
			new ItemPartsSerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				new StatementListSerializer( WbCrud::getStatementSerializer() ),
				new SitelinksSerializer( new SitelinkSerializer() )
			),
			$responseFactory,
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WbCrud::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn( RequestInterface $request ): string => $request->getPathParam( self::ITEM_ID_PATH_PARAM )
				),
			] )
		);
	}

	public function run( string $id ): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase( $id ) );
	}

	public function runUseCase( string $id ): Response {
		$fields = explode( ',', $this->getValidatedParams()[self::FIELDS_QUERY_PARAM] );

		try {
			return $this->newSuccessHttpResponse(
				$this->getItem->execute( new GetItemRequest( $id, $fields ) )
			);
		} catch ( ItemRedirect $e ) {
			return $this->newRedirectHttpResponse( $e );
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( GetItemResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream(
			json_encode( $this->itemPartsSerializer->serialize( $useCaseResponse->getItemParts() ), JSON_UNESCAPED_SLASHES )
		) );

		return $httpResponse;
	}

	private function newRedirectHttpResponse( ItemRedirect $e ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl(
				[ self::ITEM_ID_PATH_PARAM => $e->getRedirectTargetId() ],
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
				ParamValidator::PARAM_DEFAULT => implode( ',', ItemParts::VALID_FIELDS ),
			],
		];
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

}
