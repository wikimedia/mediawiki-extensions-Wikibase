<?php

namespace Wikibase\Rdf\Values;

use DataValues\QuantityValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\UnitConverter;
use Wikibase\Rdf\ValueSnakRdfBuilder;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for QuantityValue.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class QuantityRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * @var ComplexValueRdfHelper|null
	 */
	private $complexValueHelper;

	/**
	 * @var UnitConverter|null
	 */
	private $unitConverter;

	/**
	 * @param ComplexValueRdfHelper|null $complexValueHelper
	 * @param UnitConverter|null         $unitConverter
	 */
	public function __construct( ComplexValueRdfHelper $complexValueHelper = null, UnitConverter $unitConverter = null ) {
		$this->complexValueHelper = $complexValueHelper;
		$this->unitConverter = $unitConverter;
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param PropertyValueSnak $snak
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		PropertyValueSnak $snak
	) {
		/** @var QuantityValue $value */
		$value = $snak->getDataValue();
		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->value( $value->getAmount(), 'xsd', 'decimal' );
		//FIXME: this is meaningless without a unit identifier!

		if ( $this->complexValueHelper !== null ) {
			$this->addValueNode( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
		}
	}

	/**
	 * Adds a value node representing all details of $value
	 *
	 * @param RdfWriter     $writer
	 * @param string        $propertyValueNamespace Property value relation namespace
	 * @param string        $propertyValueLName Property value relation name
	 * @param string        $dataType Property data type
	 * @param QuantityValue $value
	 * @param bool          $normalized Is this a normalized value?
	 */
	private function addValueNode(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		QuantityValue $value,
		$normalized = false
	) {
		$valueLName = $this->complexValueHelper->attachValueNode(
			$writer,
			$propertyValueNamespace,
			$propertyValueLName,
			$dataType,
			$value,
			$normalized
		);

		if ( $valueLName === null ) {
			// The value node is already present in the output, don't create it again!
			return;
		}

		// Can we convert units? This condition may become more complex in the future,
		// but should keep checks for all prerequisites being set.
		$convertUnits = ( $this->unitConverter != null );

		$valueWriter = $this->complexValueHelper->getValueNodeWriter();

		$unitUri = trim( $value->getUnit() );

		if ( $unitUri === '1' ) {
			$unitUri = RdfVocabulary::ONE_ENTITY;
		}

		$normalizedValue = null;

		if ( $convertUnits && $normalized ) {
			// Normalized value is it's own normalization
			$normalizedValue = $valueLName;
		}

		// FIXME: make this depend on flavor
		// We exclude unitless ones here because same property should not have
		// both unitless and united quantity, and they are unlikely to be in the same query.
		if ( !$normalized && $convertUnits && $unitUri !== RdfVocabulary::ONE_ENTITY ) {
			$newValue = $this->unitConverter->toStandardUnits( $value );

			if ( $newValue ) {
				// Add normalized node
				$normalizedValue = $newValue->getHash();

				$this->addValueNode( $writer, $propertyValueNamespace, $propertyValueLName,
					$dataType, $newValue, true );
			}
		}
		$this->writeQuantityValue( $valueWriter, $value, $unitUri, $normalizedValue );
	}

	/**
	 * Write data for the unit.
	 * @param RdfWriter     $valueWriter
	 * @param QuantityValue $value
	 * @param string        $unitUri Unit URI
	 * @param string|null   $valueNormalized Normalized value ID
	 */
	public function writeQuantityValue( RdfWriter $valueWriter, QuantityValue $value, $unitUri,
	                                    $valueNormalized ) {
		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityAmount' )
			->value( $value->getAmount(), 'xsd', 'decimal' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUpperBound' )
			->value( $value->getUpperBound(), 'xsd', 'decimal' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityLowerBound' )
			->value( $value->getLowerBound(), 'xsd', 'decimal' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUnit' )->is( $unitUri );

		if ( $valueNormalized ) {
			$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityNormalized' )
				->is( RdfVocabulary::NS_VALUE, $valueNormalized );
		}
	}

}
