<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyPartsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreateProperty;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyRequest;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class CreatePropertyRouteHandler extends SimpleHandler {

	use AssertValidTopLevelFields;

	public const PROPERTY_BODY_PARAM = 'property';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private CreateProperty $useCase;
	private PropertyPartsSerializer $propertySerializer;

	public function __construct( CreateProperty $useCase, PropertyPartsSerializer $serializer ) {
		$this->useCase = $useCase;
		$this->propertySerializer = $serializer;
	}

	public static function factory(): Handler {
		return new self(
			new CreateProperty(
				new PropertyDeserializer(
					new LabelsDeserializer(),
					new DescriptionsDeserializer(),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
					WbRestApi::getStatementDeserializer()
				),
				WbRestApi::getPropertyUpdater()
			),
			new PropertyPartsSerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				new StatementListSerializer( WbRestApi::getStatementSerializer() )
			),
		);
	}

	public function run(): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()

		$useCaseResponse = $this->useCase->execute(
			new CreatePropertyRequest(
				$jsonBody[self::PROPERTY_BODY_PARAM],
				$jsonBody[self::TAGS_BODY_PARAM] ?? [],
				$jsonBody[self::BOT_BODY_PARAM] ?? false,
				$jsonBody[self::COMMENT_BODY_PARAM] ?? null,
				$this->getUsername()
			)
		);

		$response = $this->getResponseFactory()->create();
		$response->setStatus( 201 );
		$response->setHeader( 'Content-Type', 'application/json' );
		$response->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$response->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );

		$property = $useCaseResponse->getProperty();
		$response->setHeader(
			'Location',
			$this->getRouter()->getRouteUrl(
				GetPropertyRouteHandler::ROUTE,
				[ GetPropertyRouteHandler::PROPERTY_ID_PATH_PARAM => $property->getId() ]
			)
		);

		$response->setBody( new StringStream( json_encode(
			$this->propertySerializer->serialize( new PropertyParts(
				$property->getId(),
				PropertyParts::VALID_FIELDS,
				$property->getDataType(),
				$property->getLabels(),
				$property->getDescriptions(),
				$property->getAliases(),
				$property->getStatements(),
			) )
		) ) );

		return $response;
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyParamSettings(): array {
		return [
			self::PROPERTY_BODY_PARAM => [
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
		];
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}
