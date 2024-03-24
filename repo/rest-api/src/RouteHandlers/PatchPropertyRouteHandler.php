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
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertySerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchProperty;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchPropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchPropertyResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const PATCH_BODY_PARAM = 'patch';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private PatchProperty $useCase;
	private PropertySerializer $serializer;
	private ResponseFactory $responseFactory;

	public function __construct( PatchProperty $useCase, PropertySerializer $serializer, ResponseFactory $responseFactory ) {
		$this->useCase = $useCase;
		$this->serializer = $serializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		$labelsSerializer = new LabelsSerializer();
		$descriptionsSerializer = new DescriptionsSerializer();
		$aliasesSerializer = new AliasesSerializer();
		$statementsSerializer = new StatementListSerializer( WbRestApi::getStatementSerializer() );
		return new self(
			new PatchProperty(
				WbRestApi::getValidatingRequestDeserializer(),
				WbRestApi::getPropertyDataRetriever(),
				new PropertySerializer(
					$labelsSerializer,
					$descriptionsSerializer,
					$aliasesSerializer,
					$statementsSerializer
				),
				new PatchJson( new JsonDiffJsonPatcher() ),
				new PropertyDeserializer(
					new LabelsDeserializer(),
					new DescriptionsDeserializer(),
					new AliasesDeserializer(),
					WbRestApi::getStatementDeserializer()
				),
				WbRestApi::getPropertyUpdater()
			),
			new PropertySerializer(
				$labelsSerializer,
				$descriptionsSerializer,
				$aliasesSerializer,
				$statementsSerializer
			),
			new ResponseFactory()
		);
	}

	public function run( string $propertyId ): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyValidator()

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute(
					new PatchPropertyRequest(
						$propertyId,
						$jsonBody[self::PATCH_BODY_PARAM],
						$jsonBody[self::TAGS_BODY_PARAM],
						$jsonBody[self::BOT_BODY_PARAM],
						$jsonBody[self::COMMENT_BODY_PARAM],
						null
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( PatchPropertyResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$httpResponse->setBody(
			new StringStream( json_encode( $this->serializer->serialize( $useCaseResponse->getProperty() ) ) )
		);

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
