<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptionsRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const PATCH_BODY_PARAM = 'patch';

	private PatchItemDescriptions $useCase;
	private DescriptionsSerializer $serializer;

	public function __construct( PatchItemDescriptions $useCase, DescriptionsSerializer $serializer ) {
		$this->useCase = $useCase;
		$this->serializer = $serializer;
	}

	public static function factory(): Handler {
		return new self(
			WbRestApi::getPatchItemDescriptions(),
			new DescriptionsSerializer()
		);
	}

	public function run( string $itemId ): Response {
		return $this->newSuccessHttpResponse(
			$this->useCase->execute(
				new PatchItemDescriptionsRequest(
					$itemId,
					// @phan-suppress-next-line PhanTypeArraySuspiciousNullable
					json_decode( $this->getRequest()->getBody()->getContents(), true )[ self::PATCH_BODY_PARAM ]
				)
			)
		);
	}

	private function newSuccessHttpResponse( PatchItemDescriptionsResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody( new StringStream(
			json_encode( $this->serializer->serialize( $useCaseResponse->getDescriptions() ) )
		) );

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

}
