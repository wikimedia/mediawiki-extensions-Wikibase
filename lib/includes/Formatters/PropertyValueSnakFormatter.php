<?php

namespace Wikibase\Lib\Formatters;

use DataValues\DataValue;
use DataValues\UnDeserializableValue;
use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;

/**
 * PropertyValueSnakFormatter is a formatter for PropertyValueSnaks. This is essentially a
 * SnakFormatter adapter for TypedValueFormatter.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PropertyValueSnakFormatter implements SnakFormatter {

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * @var ValueFormatter
	 */
	private $valueFormatter;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $typeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @param string $format The name of this formatter's output format.
	 *        Use the FORMAT_XXX constants defined in SnakFormatter.
	 * @param ValueFormatter $valueFormatter
	 * @param PropertyDataTypeLookup $typeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$format,
		ValueFormatter $valueFormatter,
		PropertyDataTypeLookup $typeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		$this->format = $format;
		$this->valueFormatter = $valueFormatter;
		$this->typeLookup = $typeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * @see SnakFormatter::formatSnak
	 *
	 * Formats the given Snak by looking up its property type and calling the
	 * ValueFormatter supplied to the constructor.
	 *
	 * @param Snak $snak
	 *
	 * @throws PropertyDataTypeLookupException
	 * @throws InvalidArgumentException
	 * @throws MismatchingDataValueTypeException
	 * @throws FormattingException
	 * @return string Either plain text, wikitext or HTML, depending on the ValueFormatter
	 *  provided.
	 */
	public function formatSnak( Snak $snak ) {
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			throw new InvalidArgumentException( "Not a PropertyValueSnak: " . get_class( $snak ) );
		}

		$value = $snak->getDataValue();

		$propertyType = $this->typeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );

		if ( $value instanceof UnmappedEntityIdValue ) {
			return $this->formatValue( $value, $propertyType );
		}

		try {
			$expectedDataValueType = $this->getDataValueTypeForPropertyDataType( $propertyType );
		} catch ( OutOfBoundsException $ex ) {
			throw new FormattingException( $ex->getMessage(), 0, $ex );
		}

		if ( $expectedDataValueType !== $value->getType() ) {
			throw new MismatchingDataValueTypeException(
				$expectedDataValueType,
				$value->getType(),
				$value instanceof UnDeserializableValue
					? 'Encountered undeserializable value'
					: 'The DataValue\'s type mismatches the property\'s DataType.'
			);
		}

		return $this->formatValue( $value, $propertyType );
	}

	/**
	 * Returns the expected value type for the given property data type
	 *
	 * @param string $dataTypeId A property data type id
	 *
	 * @return string A value type
	 */
	private function getDataValueTypeForPropertyDataType( $dataTypeId ) {
		$dataType = $this->dataTypeFactory->getType( $dataTypeId );
		return $dataType->getDataValueType();
	}

	/**
	 * Calls the TypedValueFormatter passed to the constructor.
	 *
	 * @param DataValue $value
	 * @param string $dataTypeId
	 *
	 * @throws FormattingException
	 * @return string Either plain text, wikitext or HTML, depending on the ValueFormatter
	 *  provided.
	 */
	private function formatValue( DataValue $value, $dataTypeId ) {
		if ( $value instanceof UnDeserializableValue ) {
			return '';
		}

		if ( $this->valueFormatter instanceof TypedValueFormatter ) {
			return $this->valueFormatter->formatValue( $value, $dataTypeId );
		} else {
			return $this->valueFormatter->format( $value );
		}
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string One of the SnakFormatter::FORMAT_... constants.
	 */
	public function getFormat() {
		return $this->format;
	}

}
