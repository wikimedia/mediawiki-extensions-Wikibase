<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDataSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyRequest;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupPropertyDataRetriever;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyRouteHandler extends SimpleHandler {
	private const PROPERTY_ID_PATH_PARAM = 'property_id';

	private GetProperty $useCase;
	private PropertyDataSerializer $propertyDataSerializer;

	public function __construct(
		GetProperty $useCase,
		PropertyDataSerializer $propertyDataSerializer
	) {
		$this->useCase = $useCase;
		$this->propertyDataSerializer = $propertyDataSerializer;
	}

	public static function factory(): Handler {
		return new self(
			new GetProperty( new EntityRevisionLookupPropertyDataRetriever(
				WikibaseRepo::getEntityRevisionLookup(),
				new StatementReadModelConverter(
					WikibaseRepo::getStatementGuidParser(),
					WikibaseRepo::getPropertyDataTypeLookup()
				)
			) ),
			new PropertyDataSerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				WbRestApi::getSerializerFactory()->newStatementListSerializer()
			)
		);
	}

	public function run( string $propertyId ): Response {
		$useCaseResponse = $this->useCase->execute( new GetPropertyRequest( $propertyId ) );

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody( new StringStream(
			json_encode( $this->propertyDataSerializer->serialize( $useCaseResponse->getPropertyData() ), JSON_UNESCAPED_SLASHES )
		) );

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

}
