<?php
namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use DataValues\UnDeserializableValue;
use Html;
use InvalidArgumentException;
use Message;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
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
	 * Options key for controlling error handling.
	 */
	const OPT_ON_ERROR = 'on-error';

	/**
	 * Value for the OPT_ON_ERROR option indicating that recoverable
	 * errors should be ignored.
	 */
	const ON_ERROR_IGNORE = 'ignore';

	/**
	 * Value for the OPT_ON_ERROR option indicating that recoverable
	 * errors should cause a warning to be show to the user.
	 */
	const ON_ERROR_WARN = 'warn';

	/**
	 * Value for the OPT_ON_ERROR option indicating that recoverable
	 * errors should cause the formatting to fail with an exception
	 */
	const ON_ERROR_FAIL = 'fail';

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @var FormatterOptions
	 */
	private $options;

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
	 *        Use the FORMAT_XXX constants defined in SnakFormatter.
	 * @param FormatterOptions $options
	 * @param DispatchingValueFormatter $valueFormatter
	 * @param PropertyDataTypeLookup $typeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		$format,
		FormatterOptions $options,
		DispatchingValueFormatter $valueFormatter,
		PropertyDataTypeLookup $typeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		$options->defaultOption(
			self::OPT_LANG,
			'en'
		);

		$options->defaultOption(
			self::OPT_ON_ERROR,
			self::ON_ERROR_WARN
		);

		$this->format = $format;
		$this->options = $options;
		$this->valueFormatter = $valueFormatter;
		$this->typeLookup = $typeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	private function failOnErrors() {
		return $this->options->getOption( self::OPT_ON_ERROR )
			=== self::ON_ERROR_FAIL;
	}

	private function ignoreErrors() {
		return $this->options->getOption( self::OPT_ON_ERROR )
		=== self::ON_ERROR_IGNORE;
	}

	/**
	 * Formats the given Snak by looking up its property type and calling the
	 * SnakValueFormatter supplied to the constructor.
	 *
	 * @param Snak $snak
	 *
	 * @throws PropertyNotFoundException
	 * @throws InvalidArgumentException
	 * @throws MismatchingDataValueTypeException
	 * @throws FormattingException
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			throw new InvalidArgumentException( "Not a PropertyValueSnak: " . get_class( $snak ) );
		}

		$value = $snak->getDataValue();

		list( $propertyType, $warning, $value ) = $this->getEffectivePropertyDataType( $snak, $value );

		// Format the actual value, unless getEffectivePropertyDataType force the value to be null.
		if ( $value ) {
			$text = $this->formatValue( $value, $propertyType );
		} else {
			$text = '';
		}

		if ( $warning && !$this->ignoreErrors() ) {
			if ( $value ) {
				$text .= ' ';
			}

			$text .= $this->formatWarning( $warning );
		}

		return $text;
	}

	/**
	 * Determines the effective data type. The effective data type will be null
	 * if the property could not be found, or the value's actual type mismatches
	 * the data values type. Any warning is included in the return value.
	 * This method may also override the value object to actually format -
	 * in particular, it may set $value to null, to suppress rendering.
	 *
	 * @param PropertyValueSnak $snak
	 * @param DataValue $value
	 *
	 * @throws PropertyNotFoundException
	 * @throws MismatchingDataValueTypeException
	 * @return array list( $propertyType, $warning, $value )
	 */
	private function getEffectivePropertyDataType( PropertyValueSnak $snak, DataValue $value ) {
		$warning = null;
		$expectedDataValueType = null;

		// Find out the expected type for the value
		try {
			/* @var PropertyValueSnak $snak */
			$propertyType = $this->typeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
			$expectedDataValueType = $this->getDataValueTypeForPropertyDataType( $propertyType );
		} catch ( PropertyNotFoundException $ex ) {
			if ( $this->failOnErrors() ) {
				throw $ex;
			}

			$warning = wfMessage( 'wikibase-snakformatter-property-not-found',
				$snak->getPropertyId()->getSerialization() );

			$propertyType = null;
		}

		// Check that the value actually has the expected type.
		if ( $expectedDataValueType !== null && $expectedDataValueType !== $value->getType() ) {
			if ( $this->failOnErrors() ) {
				throw new MismatchingDataValueTypeException( $expectedDataValueType, $value->getType() );
			}

			if ( $value->getType() === UnDeserializableValue::getType() ) {
				// Special case: mismatch just because the value could not be unserialized.
				// Don't try to actually render the UnDeserializable value.
				// This bypasses UnDeserializableValueFormatter.
				$value = null;

				$warning = new Message( 'wikibase-undeserializable-value' );
			} else {
				if ( $this->failOnErrors() ) {
					throw new MismatchingDataValueTypeException( $expectedDataValueType, $value->getType() );
				}

				$warning = new Message( 'wikibase-snakformatter-valuetype-mismatch' );
				$warning->params( $value->getType(), $expectedDataValueType );
			}

			// Don't use property data type based formatting, since our value
			// has a type not compatible to that data type.
			$propertyType = null;
		}

		return array( $propertyType, $warning, $value );
	}

	/**
	 * @param Message $warning
	 *
	 * @return string
	 */
	private function formatWarning( Message $warning ) {
		$attributes = array( 'class' => 'error wb-format-error' );

		$lang = $this->options->getOption( self::OPT_LANG );
		$warning = $warning->inLanguage( $lang );

		//NOTE: format identifiers are MIME types, so we can just check the prefix.
		if ( strpos( $this->format, SnakFormatter::FORMAT_HTML ) === 0 ) {
			$text = $warning->parse();
			$text = Html::rawElement( 'span', $attributes, $text );

		} elseif ( $this->format === SnakFormatter::FORMAT_WIKI ) {
			$text = $warning->text();
			$text = Html::rawElement( 'span', $attributes, $text );

		} elseif ( $this->format === SnakFormatter::FORMAT_PLAIN ) {
			$text = '(' . $warning->text() . ')';

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
