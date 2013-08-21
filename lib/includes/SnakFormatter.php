<?php

namespace Wikibase\Lib;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use RuntimeException;
use Wikibase\EntityId;
use Wikibase\LanguageFallbackChain;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;

/**
 * Turns a list of Snak objects into a list of corresponding string representations.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakFormatter {

	/**
	 * @var TypedValueFormatter
	 */
	private $typedValueFormatter;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	public function __construct( PropertyDataTypeLookup $dataTypeLookup,
		TypedValueFormatter $formatter, DataTypeFactory $dataTypeFactory ) {

		$this->dataTypeLookup = $dataTypeLookup;
		$this->typedValueFormatter = $formatter;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * Turns an array of snaks into an array of strings.
	 *
	 * @since 0.4
	 *
	 * @param Snak[] $snaks
	 * @param LanguageFallbackChain|string $language language code string or LanguageFallbackChain object
	 *
	 * @return string[]
	 */
	public function formatSnaks( array $snaks, $language ) {
		$formattedValues = array();

		foreach ( $snaks as $snak ) {
			$formattedValues[] = $this->formatSnak( $snak, $language );
		}

		return $formattedValues;
	}

	private function formatSnak( Snak $snak, $language ) {
		if ( $snak instanceof PropertyValueSnak ) {
			return $this->formatPropertyValueSnak( $snak, $language );
		}

		// TODO: throw NotSupportedException
		return '';
	}

	private function formatPropertyValueSnak( PropertyValueSnak $snak, $language ) {
		$dataValue = $snak->getDataValue();
		$dataTypeId = $this->getDataTypeForProperty( $snak->getPropertyId() );

		return $this->typedValueFormatter->formatToString( $dataValue, $dataTypeId, $language );
	}

	private function getDataTypeForProperty( EntityId $propertyId ) {
		$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		$dataType = $this->dataTypeFactory->getType( $dataTypeId );

		if ( $dataType === null ) {
			throw new RuntimeException( "Could not construct DataType with unknown id '$dataTypeId'" );
		}

		return $dataType;
	}

}
