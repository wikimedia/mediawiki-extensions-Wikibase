<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelink;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelinkRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\SetSitelinkResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SetSitelinkRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const SITE_ID_PATH_PARAM = 'site_id';
	private const SITELINK_BODY_PARAM = 'sitelink';

	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private SetSitelink $useCase;
	private SiteLinkSerializer $sitelinkSerializer;
	private ResponseFactory $responseFactory;

	public function __construct( SetSitelink $useCase, ResponseFactory $responseFactory, SiteLinkSerializer $sitelinkSerializer
	) {
		$this->useCase = $useCase;
		$this->sitelinkSerializer = $sitelinkSerializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		return new self(
			WbRestApi::getSetSitelink(),
			new ResponseFactory(),
			new SitelinkSerializer(),
		);
	}

	public function run( string $itemId, string $siteId ): Response {
		$jsonBody = $this->getValidatedBody();

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
						null
					)
				)
			);
		} catch ( ItemRedirect $e ) {
			return $this->responseFactory->newErrorResponse(
				UseCaseError::ITEM_REDIRECTED,
				"Item $itemId has been merged into {$e->getRedirectTargetId()}."
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
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
			self::SITE_ID_PATH_PARAM => [
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
			] ) : parent::getBodyValidator( $contentType );
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
}
