<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\BuilderBasedDataTypeValidatorFactory;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator;

/**
 * @license GPL-2.0-or-later
 */
class TestValidatingRequestFieldDeserializerFactory {

	public const VALID_LANGUAGE_CODES = [ 'ar', 'de', 'en', 'fr' ];
	public const ALLOWED_TAGS = [ 'allowed', 'also-allowed' ];
	public const EXISTING_STRING_PROPERTY = 'P123';

	public static function newFactory( PropertyDataTypeLookup $dataTypeLookup = null ): ValidatingRequestFieldDeserializerFactory {
		$dataTypeLookup ??= new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::EXISTING_STRING_PROPERTY ), 'string' );
		$entityIdParser = new BasicEntityIdParser();
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

		return new ValidatingRequestFieldDeserializerFactory(
			new LanguageCodeValidator( self::VALID_LANGUAGE_CODES ),
			new StatementDeserializer(
				$propertyValuePairDeserializer,
				new ReferenceDeserializer( $propertyValuePairDeserializer )
			),
			new JsonDiffJsonPatchValidator(),
			new class() implements ItemLabelValidator {
				public function validate( ItemId $itemId, string $language, string $label ): ?ValidationError {
					return null;
				}
			},
			new class() implements ItemDescriptionValidator {
				public function validate( ItemId $itemId, string $language, string $description ): ?ValidationError {
					return null;
				}
			},
			500,
			self::ALLOWED_TAGS
		);
	}

}
