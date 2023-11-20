<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemLabelRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const LANGUAGE_CODE_PATH_PARAM = 'language_code';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private const TAGS_PARAM_DEFAULT = [];
	private const BOT_PARAM_DEFAULT = false;
	private const COMMENT_PARAM_DEFAULT = null;

	private RemoveItemLabel $removeItemLabel;
	private ResponseFactory $responseFactory;

	public function __construct(
		RemoveItemLabel $removeItemLabel,
		ResponseFactory $responseFactory
	) {
		$this->removeItemLabel = $removeItemLabel;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		return new self(
			new RemoveItemLabel(
				WbRestApi::getValidatingRequestDeserializer(),
				WbRestApi::getAssertItemExists(),
				WbRestApi::getItemDataRetriever(),
				WbRestApi::getItemUpdater()
			),
			new ResponseFactory()
		);
	}

	public function run( string $itemId, string $languageCode ): Response {
		$requestBody = $this->getValidatedBody();

		try {
			$this->removeItemLabel->execute(
				new RemoveItemLabelRequest(
					$itemId,
					$languageCode,
					$requestBody[ self::TAGS_BODY_PARAM ] ?? self::TAGS_PARAM_DEFAULT,
					$requestBody[ self::BOT_BODY_PARAM ] ?? self::BOT_PARAM_DEFAULT,
					$requestBody[ self::COMMENT_BODY_PARAM ] ?? self::COMMENT_PARAM_DEFAULT,
					null
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		} catch ( ItemRedirect $e ) {
			return $this->responseFactory->newErrorResponse(
				UseCaseError::ITEM_REDIRECTED,
				"Item $itemId has been merged into {$e->getRedirectTargetId()}."
			);
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

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return $contentType === 'application/json' ?
			new TypeValidatingJsonBodyValidator( [
				self::TAGS_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'array',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => self::TAGS_PARAM_DEFAULT,
				],
				self::BOT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'boolean',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => self::BOT_PARAM_DEFAULT,
				],
				self::COMMENT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => self::COMMENT_PARAM_DEFAULT,
				],
			] ) : parent::getBodyValidator( $contentType );
	}

}
