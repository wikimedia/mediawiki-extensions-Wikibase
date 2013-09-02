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
class PropertyValueSnakFormatter implements SnakFormatter {

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @var ValueFormatter[]
	 */
	private $formatters;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $typeLookup;

	/**
	 * @param string $format The name of this formatter's output format.
	 *        Use the FORMAT_XXX constants defined in SnakFormatterFactory.
	 * @param ValueFormatter[] $formatters Maps prefixed type ids to ValueFormatter instances.
	 *        Each type ID must be prefixed with either "PT:" for property data types
	 *        or "VT:" fordata value types.
	 * @param PropertyDataTypeLookup $typeLookup
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $format, array $formatters, PropertyDataTypeLookup $typeLookup) {

		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		foreach ( $formatters as $type => $formatter ) {
			if ( !is_string( $type ) ) {
				throw new InvalidArgumentException( '$formatters must map type IDs to formatters.' );
			}

			if ( !preg_match( '/^(PT|VT):/', $type ) ) {
				throw new InvalidArgumentException( 'Type ID must be prefixed with "PT:" or "VT:" to'
						. ' indicate property data type or data value type, respectively.' );
			}

			if ( !( $formatter instanceof ValueFormatter ) ) {
				throw new InvalidArgumentException( '$formatters must contain instances of ValueFormatter' );
			}
		}

		$this->format     = $format;
		$this->formatters = $formatters;
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
	 * Formats the given value by finding an appropriate formatter among the ones supplied
	 * to the constructor, and applying it.
	 *
	 * If $dataTypeId is given, this will first try to find an appropriate formatter based on
	 * the data type. If none is found, this falls back to finding a formatter based on the
	 * value's type.
	 *
	 * @param DataValue $value
	 * @param string    $dataTypeId
	 *
	 * @throws FormattingException
	 * @return string
	 */
	public function formatValue( DataValue $value, $dataTypeId = null ) {
		$formatter = $this->getFormatter( $value->getType(), $dataTypeId );

		$text = $formatter->format( $value );
		return $text;
	}

	/**
	 * Finds an appropriate formatter among the ones supplied to the constructor.
	 *
	 * If $dataTypeId is given, this will first try to find an appropriate formatter based on
	 * the data type. If none is found, this falls back to finding a formatter based
	 * on $dataValueType.
	 *
	 * @param string $dataValueType
	 * @param string|null $dataTypeId
	 *
	 * @return ValueFormatter
	 * @throws FormattingException if no appropriate formatter is found
	 */
	public function getFormatter( $dataValueType, $dataTypeId = null ) {
		/* @var ValueFormatter */
		$formatter = null;

		if ( $dataTypeId !== null ) {
			if ( isset( $this->formatters["PT:$dataTypeId"] ) ) {
				$formatter = $this->formatters["PT:$dataTypeId"];
			}
		}

		if ( $formatter === null ) {
			if ( isset( $this->formatters["VT:$dataValueType"] ) ) {
				$formatter = $this->formatters["VT:$dataValueType"];
			}
		}

		if ( $formatter === null ) {
			if ( $dataTypeId !== null ) {
				$msg = "No formatter defined for data type $dataTypeId nor for value type $dataValueType.";
			} else {
				$msg = "No formatter defined for value type $dataValueType.";
			}

			throw new FormattingException( $msg );
		}

		return $formatter;
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}
}