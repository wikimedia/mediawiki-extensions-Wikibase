<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\ItemPartsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItem;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItemResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\BotRightCheckMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class CreateItemRouteHandler extends SimpleHandler {

	use AssertContentType;
	use AssertValidTopLevelFields;

	private const ITEM_BODY_PARAM = 'item';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private CreateItem $useCase;
	private ItemPartsSerializer $itemSerializer;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		CreateItem $useCase,
		ItemPartsSerializer $serializer,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->useCase = $useCase;
		$this->itemSerializer = $serializer;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();
		return new self(
			WbRestApi::getCreateItem(),
			new ItemPartsSerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				new StatementListSerializer( WbRestApi::getStatementSerializer() ),
				new SitelinksSerializer( new SitelinkSerializer() )
			),
			$responseFactory,
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware(),
				new BotRightCheckMiddleware( MediaWikiServices::getInstance()->getPermissionManager(), $responseFactory ),
			] )
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase(): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per parseBodyData()

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute(
					new CreateItemRequest(
						$jsonBody[self::ITEM_BODY_PARAM],
						$jsonBody[self::TAGS_BODY_PARAM] ?? [],
						$jsonBody[self::BOT_BODY_PARAM] ?? false,
						$jsonBody[self::COMMENT_BODY_PARAM] ?? null,
						$this->getUsername()
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	public function parseBodyData( RequestInterface $request ): ?array {
		$this->assertContentType( [ 'application/json' ], $request->getBodyType() ?? 'unknown' );
		$body = parent::parseBodyData( $request );
		$this->assertValidTopLevelTypes( $body, $this->getBodyParamSettings() );

		return $body;
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_BODY_PARAM => [
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

	private function newSuccessHttpResponse( CreateItemResponse $useCaseResponse ): Response {
		$response = $this->getResponseFactory()->create();
		$response->setStatus( 201 );
		$response->setHeader( 'Content-Type', 'application/json' );
		$response->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$response->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$item = $useCaseResponse->getItem();
		$response->setHeader(
			'Location',
			$this->getRouter()->getRouteUrl(
				GetItemRouteHandler::ROUTE,
				[ GetItemRouteHandler::ITEM_ID_PATH_PARAM => $item->getId() ]
			)
		);
		$response->setBody( new StringStream( json_encode(
			$this->itemSerializer->serialize( new ItemParts(
				$item->getId(),
				ItemParts::VALID_FIELDS,
				$item->getLabels(),
				$item->getDescriptions(),
				$item->getAliases(),
				$item->getStatements(),
				$item->getSitelinks()
			) )
		) ) );

		return $response;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
