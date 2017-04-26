<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use DataValues\UnDeserializableValue;
use InvalidArgumentException;
use Message;
use OutOfBoundsException;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\TypedValueFormatter;

/**
 * PropertyValueSnakFormatter is a formatter for PropertyValueSnaks. This is essentially a
 * SnakFormatter adapter for TypedValueFormatter.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyValueSnakFormatter implements SnakFormatter {

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * @var FormatterOptions
	 */
	private $options;

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
	 * @param FormatterOptions|null $options
	 * @param ValueFormatter $valueFormatter
	 * @param PropertyDataTypeLookup $typeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$format,
		FormatterOptions $options = null,
		ValueFormatter $valueFormatter,
		PropertyDataTypeLookup $typeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		$this->format = $format;
		$this->options = $options ?: new FormatterOptions();
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

		$propertyType = null;
		$value = $snak->getDataValue();

		try {
			$propertyType = $this->typeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
			$expectedDataValueType = $this->getDataValueTypeForPropertyDataType( $propertyType );
		} catch ( PropertyDataTypeLookupException $ex ) {
			throw $ex;
		} catch ( OutOfBoundsException $ex ) {
			throw new FormattingException( $ex->getMessage(), 0, $ex );
		}

		$this->checkValueType( $value, $expectedDataValueType );

		return $this->formatValue( $value, $propertyType );
	}

	/**
	 * @param DataValue $value
	 *
	 * @return boolean
	 */
	private function isUnDeserializableValue( DataValue $value ) {
		return $value->getType() === UnDeserializableValue::getType();
	}

	/**
	 * @param DataValue $value
	 * @param string $expectedDataValueType
	 *
	 * @throws PropertyDataTypeLookupException
	 * @throws MismatchingDataValueTypeException
	 * @return Message|null
	 */
	private function checkValueType( DataValue $value, $expectedDataValueType ) {
		$warning = null;

		if ( $this->isUnDeserializableValue( $value ) ) {
			throw new MismatchingDataValueTypeException(
				$expectedDataValueType,
				$value->getType(),
				'Encountered undeserializable value'
			);
		} elseif ( $expectedDataValueType !== $value->getType() ) {
			throw new MismatchingDataValueTypeException(
				$expectedDataValueType,
				$value->getType(),
				'The DataValue\'s type mismatches the property\'s DataType.'
			);
		}

		return $warning;
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
	 * @param string|null $dataTypeId
	 *
	 * @throws FormattingException
	 * @return string Either plain text, wikitext or HTML, depending on the ValueFormatter
	 *  provided.
	 */
	private function formatValue( DataValue $value, $dataTypeId = null ) {
		if ( !$this->isUnDeserializableValue( $value ) ) {
			if ( $this->valueFormatter instanceof TypedValueFormatter ) {
				$text = $this->valueFormatter->formatValue( $value, $dataTypeId );
			} else {
				$text = $this->valueFormatter->format( $value );
			}
		} else {
			$text = '';
		}

		return $text;
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
