<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use DataValues\Deserializers\DataValueDeserializer;
use LogicException;
use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\BuilderBasedDataTypeValidatorFactory;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\RestApi\Infrastructure\ValidatingRequestDeserializer as VRD;

/**
 * @license GPL-2.0-or-later
 */
class TestValidatingRequestDeserializerServiceContainer implements ContainerInterface {

	/**
	 * Returns the real implementation for most validators, and test doubles for some that require certain database data, e.g. depend on a
	 * property data type lookup.
	 * @inheritDoc
	 */
	public function get( string $id ) {
		switch ( $id ) {
			case VRD::EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER:
				return new EditMetadataRequestValidatingDeserializer(
					new EditMetadataValidator( 500, TestValidatingRequestDeserializer::ALLOWED_TAGS )
				);
			case VRD::STATEMENT_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER:
				$entityIdParser = new BasicEntityIdParser();
				$dataTypeLookup = new InMemoryDataTypeLookup();
				$dataTypeLookup->setDataTypeForProperty(
					new NumericPropertyId( TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY ),
					'string'
				);
				$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
					$entityIdParser,
					$dataTypeLookup,
					new DataValuesValueDeserializer(
						new DataTypeFactoryValueTypeLookup( new DataTypeFactory( [] ) ),
						$entityIdParser,
						new DataValueDeserializer( [] ),
						new BuilderBasedDataTypeValidatorFactory( [] )
					)
				);

				return new StatementSerializationRequestValidatingDeserializer(
					new StatementValidator(
						new StatementDeserializer(
							$propertyValuePairDeserializer,
							new ReferenceDeserializer( $propertyValuePairDeserializer )
						)
					)
				);
		}
		return MediaWikiServices::getInstance()->get( $id );
	}

	/**
	 * @inheritDoc
	 */
	public function has( string $id ): bool {
		throw new LogicException( 'This is not expected to be called.' );
	}
}
