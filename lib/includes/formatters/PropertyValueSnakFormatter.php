<?php
namespace Wikibase\Lib;
use DataValues\DataValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;
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
	 * @param string $format The name of this formatter's output format.
	 *        Use the FORMAT_XXX constants defined in OutputFormatSnakFormatterFactory.
	 * @param DispatchingValueFormatter $valueFormatter
	 * @param PropertyDataTypeLookup $typeLookup
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $format, DispatchingValueFormatter $valueFormatter, PropertyDataTypeLookup $typeLookup) {
		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		$this->format = $format;
		$this->valueFormatter = $valueFormatter;
		$this->typeLookup = $typeLookup;
	}

	/**
	 * Formats the given Snak by looking up its property type and calling the
	 * SnakValueFormatter supplied to the constructor.
	 *
	 * @param Snak $snak
	 *
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			throw new InvalidArgumentException( "Not a PropertyValueSnak: " . get_class( $snak ) );
		}

		/* @var PropertyValueSnak $snak */
		$propertyType = $this->typeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );

		$text = $this->formatValue( $snak->getDataValue(), $propertyType );
		return $text;
	}

	/**
	 * @see ValueFormatter::format().
	 *
	 * Implemented by delegating to the DispatchingValueFormatter passed to the constructor.
	 *
	 * @see TypedValueFormatter::formatValue.
	 *
	 * @param DataValue $value
	 * @param string    $dataTypeId
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
