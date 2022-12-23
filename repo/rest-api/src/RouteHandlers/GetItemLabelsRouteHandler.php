<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabelsSuccessResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelsRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';

	private GetItemLabels $useCase;
	private LabelsSerializer $labelsSerializer;

	public function __construct( GetItemLabels $useCase, LabelsSerializer $labelsSerializer ) {
		$this->useCase = $useCase;
		$this->labelsSerializer = $labelsSerializer;
	}

	public static function factory(): self {
		return new self(
			WbRestApi::getGetItemLabels(),
			new LabelsSerializer()
		);
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	public function run( string $itemId ): Response {
		$useCaseResponse = $this->useCase->execute( new GetItemLabelsRequest( $itemId ) );
		return $this->newSuccessHttpResponse( $useCaseResponse );
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}

	private function newSuccessHttpResponse( GetItemLabelsSuccessResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream( json_encode( $this->labelsSerializer->serialize( $useCaseResponse->getLabels() ) ) ) );

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
	}

}
