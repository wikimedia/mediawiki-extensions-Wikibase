<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabelRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const LANGUAGE_CODE_PATH_PARAM = 'language_code';
	public const LABEL_BODY_PARAM = 'label';

	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private SetItemLabel $setItemLabel;

	public function __construct( SetItemLabel $setItemLabel ) {
		$this->setItemLabel = $setItemLabel;
	}

	public static function factory(): Handler {
		return new self( WbRestApi::getSetItemLabel() );
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return call_user_func( [ $this, 'runUseCase' ], ...$args );
	}

	public function runUseCase( string $itemId, string $languageCode ): Response {
		$jsonBody = $this->getValidatedBody();
		$useCaseResponse = $this->setItemLabel->execute(
			new SetItemLabelRequest(
				$itemId,
				$languageCode,
				$jsonBody[self::LABEL_BODY_PARAM],
				$jsonBody[self::TAGS_BODY_PARAM],
				$jsonBody[self::BOT_BODY_PARAM],
				$jsonBody[self::COMMENT_BODY_PARAM]
			)
		);
		return $this->newSuccessHttpResponse( $useCaseResponse );
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

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return $contentType === 'application/json' ?
			new TypeValidatingJsonBodyValidator( [
				self::LABEL_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'string',
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
			] ) : parent::getBodyValidator( $contentType );
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

	private function newSuccessHttpResponse( SetItemLabelResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setBody( new StringStream( json_encode( $useCaseResponse->getLabel()->getText() ) ) );

		return $httpResponse;
	}

}
