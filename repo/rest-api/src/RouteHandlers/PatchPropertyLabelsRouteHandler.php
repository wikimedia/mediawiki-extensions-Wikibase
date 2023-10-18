<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabelsRequest;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\TermLookupEntityTermsRetriever;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyLabelsRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const PATCH_BODY_PARAM = 'patch';

	private PatchPropertyLabels $useCase;
	private LabelsSerializer $serializer;

	public function __construct( PatchPropertyLabels $useCase, LabelsSerializer $serializer ) {
		$this->useCase = $useCase;
		$this->serializer = $serializer;
	}

	public static function factory(): Handler {
		$serializer = new LabelsSerializer();
		return new self(
			new PatchPropertyLabels(
				new TermLookupEntityTermsRetriever(
					WikibaseRepo::getTermLookup(),
					WikibaseRepo::getTermsLanguages()
				),
				$serializer,
				new JsonDiffJsonPatcher(),
				new LabelsDeserializer(),
				WbRestApi::getPropertyDataRetriever(),
				WbRestApi::getPropertyUpdater()
			),
			$serializer
		);
	}

	public function run( string $propertyId ): Response {
		$jsonBody = $this->getValidatedBody();

		$useCaseResponse = $this->useCase->execute(
			new PatchPropertyLabelsRequest(
				$propertyId,
				$jsonBody[self::PATCH_BODY_PARAM]
			)
		);

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setBody( new StringStream( json_encode(
			$this->serializer->serialize( $useCaseResponse->getLabels() )
		) ) );

		return $httpResponse;
	}

	public function getParamSettings(): array {
		return [
			self::PROPERTY_ID_PATH_PARAM => [
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
		return new JsonBodyValidator( [
			self::PATCH_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => true,
			],
		] );
	}

}
