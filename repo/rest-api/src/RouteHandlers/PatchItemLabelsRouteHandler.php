<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\TermLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabelsRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const PATCH_BODY_PARAM = 'patch';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private PatchItemLabels $useCase;
	private LabelsSerializer $serializer;
	private ResponseFactory $responseFactory;

	public function __construct( PatchItemLabels $useCase, LabelsSerializer $serializer, ResponseFactory $responseFactory ) {
		$this->useCase = $useCase;
		$this->serializer = $serializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		$serializer = new LabelsSerializer();
		return new self(
			new PatchItemLabels(
				new TermLookupItemDataRetriever(
					WikibaseRepo::getTermLookup(),
					WikibaseRepo::getTermsLanguages()
				),
				$serializer,
				new JsonDiffJsonPatcher(),
				new LabelsDeserializer(),
				WbRestApi::getItemDataRetriever(),
				WbRestApi::getItemUpdater(),
				new WikibaseEntityPermissionChecker(
					WikibaseRepo::getEntityPermissionChecker(),
					MediaWikiServices::getInstance()->getUserFactory()
				)
			),
			$serializer,
			new ResponseFactory()
		);
	}

	public function run( string $itemId ): Response {
		$jsonBody = $this->getValidatedBody();

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute(
					new PatchItemLabelsRequest(
						$itemId,
						$jsonBody[self::PATCH_BODY_PARAM],
						$jsonBody[self::TAGS_BODY_PARAM] ?? [],
						$jsonBody[self::BOT_BODY_PARAM] ?? false,
						$jsonBody[self::COMMENT_BODY_PARAM] ?? '',
						$this->getUsername()
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( PatchItemLabelsResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setBody(
			new StringStream(
				json_encode(
					$this->serializer->serialize( $useCaseResponse->getLabels() )
				)
			)
		);

		return $httpResponse;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
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
		return new JsonBodyValidator( [] );
	}
}
