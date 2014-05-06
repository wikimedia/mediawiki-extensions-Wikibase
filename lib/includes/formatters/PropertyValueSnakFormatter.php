<?php
namespace Wikibase\Lib;

use DataValues\DataValue;
use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;

/**
 * PropertyValueSnakFormatter is a formatter for PropertyValueSnaks. It allows formatters to
 * be applied either per property data type or, as a fallback, per data value type.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class PropertyValueSnakFormatter implements SnakFormatter, TypedValueFormatter {

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @var DispatchingValueFormatter
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
	 *		Use the FORMAT_XXX constants defined in OutputFormatSnakFormatterFactory.
	 * @param DispatchingValueFormatter $valueFormatter
	 * @param PropertyDataTypeLookup $typeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $format, DispatchingValueFormatter $valueFormatter,
		PropertyDataTypeLookup $typeLookup, DataTypeFactory $dataTypeFactory
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
	 * Formats the given Snak by looking up its property type and calling the
	 * SnakValueFormatter supplied to the constructor.
	 *
	 * @param Snak $snak
	 *
	 * @throws \InvalidArgumentException
	 * @throws MismatchingDataValueTypeException
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			throw new InvalidArgumentException( "Not a PropertyValueSnak: " . get_class( $snak ) );
		}

		$propertyType = $this->getPropertyType( $snak );
		$dataValue = $snak->getDataValue();

		if ( $propertyType !== null ) {
			$this->checkDataValueTypeMismatch( $dataValue, $propertyType );
		}

		$text = $this->formatValue( $dataValue, $propertyType );
		return $text;
	}

	/**
	 * @param DataValue $dataValue
	 * @param string $propertyType
	 *
	 * @throws MismatchingDataValueTypeException
	 */
	private function checkDataValueTypeMismatch( $dataValue, $propertyType ) {
		$dataType = $this->dataTypeFactory->getType( $propertyType );
		$dataTypeValueType = $dataType->getDataValueType();
		$dataValueType = $dataValue->getType();

		if ( $dataTypeValueType !== $dataValueType ) {
			throw new MismatchingDataValueTypeException( $dataValueType, $dataTypeValueType );
		}
	}

	/**
	 * @param Snak $snak
	 *
	 * @return string|null
	 */
	private function getPropertyType( Snak $snak ) {
		try {
			$propertyId = $snak->getPropertyId();
			$propertyType = $this->typeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( PropertyNotFoundException $ex ) {
			// If the property has been removed, we should still be able to render the snak
			// value, so don't fail here.
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Can\'t look up data type for property '
				. $snak->getPropertyId()->getPrefixedId() );
			$propertyType = null;
		}

		return $propertyType;
	}

	/**
	 * @see ValueFormatter::format().
	 *
	 * Implemented by delegating to the DispatchingValueFormatter passed to the constructor.
	 *
	 * @see TypedValueFormatter::formatValue.
	 *
	 * @param DataValue $value
	 * @param string $dataTypeId
	 *
	 * @throws FormattingException
	 * @return string
	 */
	public function formatValue( DataValue $value, $dataTypeId = null ) {
		return $this->valueFormatter->formatValue( $value, $dataTypeId );
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Checks whether the given snak's type is 'value'.
	 *
	 * @see SnakFormatter::canFormatSnak()
	 *
	 * @param Snak $snak
	 *
	 * @return bool
	 */
	public function canFormatSnak( Snak $snak ) {
		return $snak->getType() === 'value';
	}
}
