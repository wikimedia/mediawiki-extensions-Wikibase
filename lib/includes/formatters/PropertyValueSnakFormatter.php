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
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\PropertyInfoStore;

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
	 * @var TypedValueFormatter
	 */
	private $valueFormatter;

	/**
	 * @var PropertyInfoStore
	 */
	private $propertyInfoStore;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $fallbackLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @param string $format The name of this formatter's output format.
	 *        Use the FORMAT_XXX constants defined in SnakFormatter.
	 * @param FormatterOptions|null $options
	 * @param TypedValueFormatter $valueFormatter
	 * @param PropertyInfoStore $propertyInfoStore
	 * @param PropertyDataTypeLookup $fallbackLookup
	 * @param DataTypeFactory $dataTypeFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$format,
		FormatterOptions $options = null,
		TypedValueFormatter $valueFormatter,
		PropertyInfoStore $propertyInfoStore,
		PropertyDataTypeLookup $fallbackLookup,
		DataTypeFactory $dataTypeFactory
	) {
		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		$this->format = $format;
		$this->options = $options ?: new FormatterOptions();
		$this->valueFormatter = $valueFormatter;
		$this->propertyInfoStore = $propertyInfoStore;
		$this->fallbackLookup = $fallbackLookup;
		$this->dataTypeFactory = $dataTypeFactory;

		$this->options->defaultOption( self::OPT_LANG, 'en' );
		$this->options->defaultOption( self::OPT_ON_ERROR, self::ON_ERROR_WARN );
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
	 * @throws PropertyDataTypeLookupException
	 * @throws InvalidArgumentException
	 * @throws MismatchingDataValueTypeException
	 * @throws FormattingException
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			throw new InvalidArgumentException( "Not a PropertyValueSnak: " . get_class( $snak ) );
		}

		$dataTypeId = null;
		$value = $snak->getDataValue();

		try {
			$dataTypeId = $this->fetchDataTypeFromPropertyInfo( $snak->getPropertyId() );
			$expectedDataValueType = $this->getDataValueTypeForPropertyDataType( $dataTypeId );

			$warning = $this->checkForWarning( $value, $expectedDataValueType );
		} catch ( PropertyDataTypeLookupException $ex ) {
			if ( $this->failOnErrors() ) {
				throw $ex;
			}

			$warning = new Message(
				'wikibase-snakformatter-property-not-found',
				array( $snak->getPropertyId()->getSerialization() )
			);
		}

		if ( isset( $warning ) && !$this->ignoreErrors() ) {
			$text = $this->formatValueWithWarning( $value, $warning );
		} else {
			$text = $this->formatValue( $value, $dataTypeId );
		}

		return $text;
	}

	/**
	 * @param DataValue $value
	 * @param Message $warning
	 *
	 * @return string
	 */
	private function formatValueWithWarning( DataValue $value, Message $warning ) {
		$text = $this->formatValue( $value );

		if ( $text !== '' ) {
			$text .= ' ';
		}

		$text .= $this->formatWarning( $warning );

		return $text;
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
	private function checkForWarning( DataValue $value, $expectedDataValueType ) {
		$warning = null;

		if ( $this->isUnDeserializableValue( $value ) ) {
			if ( $this->failOnErrors() ) {
				throw new MismatchingDataValueTypeException(
					$expectedDataValueType,
					$value->getType(),
					'Encountered undeserializable value'
				);
			}

			$warning = new Message( 'wikibase-undeserializable-value' );
		} elseif ( $expectedDataValueType !== $value->getType() ) {
			if ( $this->failOnErrors() ) {
				throw new MismatchingDataValueTypeException(
					$expectedDataValueType,
					$value->getType(),
					'The DataValue\'s type mismatches the property\'s DataType.'
				);
			}

			$warning = new Message(
				'wikibase-snakformatter-valuetype-mismatch',
				array( $value->getType(), $expectedDataValueType )
			);
		}

		return $warning;
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
	 * Fetches the property info for the given property id and returns the data type.
	 * If the data type isn't set it tries to fallback to a data type lookup.
	 * As a side effect, the formatter url gets put into the FormatterOptions.
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 */
	private function fetchDataTypeFromPropertyInfo( PropertyId $propertyId ) {
		$dataTypeId = null;
		$propertyInfo = $this->propertyInfoStore->getPropertyInfo( $propertyId );

		if ( $propertyInfo !== null ) {
			if ( isset( $propertyInfo[PropertyInfoStore::KEY_FORMATTER_URL] ) ) {
				$this->options->setOption(
					PropertyInfoStore::KEY_FORMATTER_URL,
					$propertyInfo[PropertyInfoStore::KEY_FORMATTER_URL]
				);
			}

			if ( isset( $propertyInfo[PropertyInfoStore::KEY_DATA_TYPE] ) ) {
				$dataTypeId = $propertyInfo[PropertyInfoStore::KEY_DATA_TYPE];
			}
		}

		if ( $dataTypeId === null && $this->fallbackLookup !== null ) {
			$dataTypeId = $this->fallbackLookup->getDataTypeIdForProperty( $propertyId );

			wfDebugLog( __CLASS__, __FUNCTION__ . ': No property info found for '
				. $propertyId . ', but property ID could be retrieved from fallback store!' );
		}

		if ( $dataTypeId === null ) {
			throw new PropertyDataTypeLookupException( $propertyId );
		}

		return $dataTypeId;
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
	 * @see ValueFormatter::format
	 *
	 * Implemented by delegating to the TypedValueFormatter passed to the constructor.
	 *
	 * @see TypedValueFormatter::formatValue
	 *
	 * @param DataValue $value
	 * @param string $dataTypeId
	 *
	 * @throws FormattingException
	 * @return string
	 */
	public function formatValue( DataValue $value, $dataTypeId = null ) {
		if ( !$this->isUnDeserializableValue( $value ) ) {
			$text = $this->valueFormatter->formatValue( $value, $dataTypeId );
		} else {
			$text = '';
		}

		return $text;
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
	 * @see SnakFormatter::canFormatSnak
	 *
	 * @param Snak $snak
	 *
	 * @return bool
	 */
	public function canFormatSnak( Snak $snak ) {
		return $snak->getType() === 'value';
	}

}
