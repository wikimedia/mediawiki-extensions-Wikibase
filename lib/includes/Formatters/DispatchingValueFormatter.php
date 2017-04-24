<?php

namespace Wikibase\Lib\Formatters;

use DataValues\DataValue;
use DataValues\IllegalValueException;
use InvalidArgumentException;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;

/**
 * DispatchingValueFormatter is a formatter for DataValues. In addition to dispatching based on
 * the DataValue type, it also supports dispatching based on a DataType.
 *
 * @todo: Plain format() shouldn't be supported,
 * formatValue() should require the dataType ID.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DispatchingValueFormatter implements ValueFormatter, TypedValueFormatter {

	/**
	 * @var ValueFormatter[]
	 */
	private $formatters;

	/**
	 * @param ValueFormatter[] $formatters Maps prefixed type ids to ValueFormatter instances.
	 *        Each type ID must be prefixed with either "PT:" for property data types
	 *        or "VT:" for data value types.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $formatters ) {
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

		$this->formatters = $formatters;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given value by finding an appropriate formatter among the ones supplied
	 * to the constructor, and applying it.
	 *
	 * If $dataTypeId is given, this will first try to find an appropriate formatter based on
	 * the data type. If none is found, this falls back to finding a formatter based on the
	 * value's type.
	 *
	 * @see TypedValueFormatter::formatValue
	 *
	 * @param DataValue $value
	 * @param string|null $dataTypeId
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
	 * @see ValueFormatter::format
	 *
	 * @deprecated, use formatValue() instead
	 *
	 * @param DataValue $value The value to format
	 *
	 * @throws IllegalValueException
	 * @return string
	 */
	public function format( $value ) {
		if ( !( $value instanceof DataValue ) ) {
			throw new IllegalValueException( '$value must be a DataValue' );
		}

		return $this->formatValue( $value );
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
	protected function getFormatter( $dataValueType, $dataTypeId = null ) {
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

}
