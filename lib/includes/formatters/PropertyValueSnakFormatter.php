<?php
namespace Wikibase\Lib;
use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use DataValues\UnDeserializableValue;
use Html;
use InvalidArgumentException;
use Message;
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
	 *        Use the FORMAT_XXX constants defined in OutputFormatSnakFormatterFactory.
	 * @param DispatchingValueFormatter $valueFormatter
	 * @param PropertyDataTypeLookup $typeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		$format,
		DispatchingValueFormatter $valueFormatter,
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

		$value = $snak->getDataValue();
		$warning = null;

		try {
			/* @var PropertyValueSnak $snak */
			$propertyType = $this->typeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
			$expectedDataValueType = $this->getDataValueTypeForPropertyDataType( $propertyType );

			// Check that the value actually has the expected type.
			if ( $expectedDataValueType !== null
				&& $expectedDataValueType !== $value->getType() ) {

				// Warn, but only if the value isn't "bad"; no point to complain again in that case.
				if ( $value->getType() === UnDeserializableValue::getType() ) {
					wfWarn( __METHOD__ . ': Encountered undeserializable value '
						. $snak->getPropertyId()->getPrefixedId() );

					// NOTE: don't set a warning here, that's handled by UnDeserializableValueFormatter
				} else {
					wfWarn( __METHOD__ . ': Mismatching value type: Peroperty '
						. $snak->getPropertyId() . ' expects a '
						. $expectedDataValueType . ', but snak contains a '
						. $value->getType() );

					$warning = wfMessage( 'wikibase-snakview-variation-datavaluetypemismatch-details',
						$value->getType(),
						$expectedDataValueType );
				}

				// Don't use property data type based formatting, since our value
				// has a type not compatible to that data type.
				$propertyType = null;
			}
		} catch ( PropertyNotFoundException $ex ) {
			// If the property has been removed, we should still be able to render the snak value, so don't fail here.
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Can\'t look up data type for property '
				. $snak->getPropertyId()->getPrefixedId() );

			$warning = wfMessage( 'wikibase-snakformat-propertynotfound' );
			$propertyType = null;
		}

		$text = $this->formatValue( $value, $propertyType );

		if ( $warning ) {
			$text .= ' ' . $this->formatWarning( $warning );
		}

		return $text;
	}

	/**
	 * @param Message $warning
	 *
	 * @return string
	 */
	private function formatWarning( Message $warning ) {
		$attributes = array( 'class' => 'error' );

		//NOTE: format identifiers are MIME types, so we can just check the prefix.
		if ( strpos( $this->format, SnakFormatter::FORMAT_HTML ) === 0 ) {
			$text = '(' . $warning->parse() . ')';
			$text = Html::rawElement( 'span', $attributes, $text );

		} elseif ( $this->format === SnakFormatter::FORMAT_WIKI ) {
			$text = '(' . $warning->text() . ')';
			$text = Html::rawElement( 'span', $attributes, $text );

		} elseif ( $this->format === SnakFormatter::FORMAT_PLAIN ) {
			$text = $warning->text();

		} else {
			$text = '';
		}

		return $text;
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
