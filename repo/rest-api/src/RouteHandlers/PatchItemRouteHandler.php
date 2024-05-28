<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ItemSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchItem;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchItemResponse;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const PATCH_BODY_PARAM = 'patch';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private PatchItem $useCase;
	private ItemSerializer $serializer;
	private ResponseFactory $responseFactory;

	public function __construct( PatchItem $useCase, ItemSerializer $serializer, ResponseFactory $responseFactory ) {
		$this->useCase = $useCase;
		$this->serializer = $serializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		$itemSerializer = new ItemSerializer(
			new LabelsSerializer(),
			new DescriptionsSerializer(),
			new AliasesSerializer(),
			new StatementListSerializer( WbRestApi::getStatementSerializer() ),
			new SitelinksSerializer( new SitelinkSerializer() )
		);
		return new self(
			new PatchItem(
				WbRestApi::getValidatingRequestDeserializer(),
				WbRestApi::getItemDataRetriever(),
				$itemSerializer,
				new ItemDeserializer(
					new LabelsDeserializer(),
					new DescriptionsDeserializer(),
					new AliasesDeserializer(),
					WbRestApi::getStatementDeserializer(),
					WbRestApi::getSitelinkDeserializer()
				),
				new PatchJson( new JsonDiffJsonPatcher() ),
				WbRestApi::getItemUpdater()
			),
			$itemSerializer,
			new ResponseFactory()
		);
	}

	public function run( string $itemId ): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyValidator()

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute(
					new PatchItemRequest(
						$itemId,
						$jsonBody[ self::PATCH_BODY_PARAM ],
						$jsonBody[ self::TAGS_BODY_PARAM ],
						$jsonBody[ self::BOT_BODY_PARAM ],
						$jsonBody[ self::COMMENT_BODY_PARAM ],
						null
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( PatchItemResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$httpResponse->setBody(
			new StringStream( json_encode( $this->serializer->serialize( $useCaseResponse->getItem() ) ) )
		);

		return $httpResponse;
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

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return new TypeValidatingJsonBodyValidator( [
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
		] );
	}

}
