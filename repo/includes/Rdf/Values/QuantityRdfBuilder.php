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
			$valueLName = $this->addValueNode(
				$writer,
				$propertyValueNamespace,
				$propertyValueLName,
				$dataType,
				$value
			);

			// Can we convert units? This condition may become more complex in the future,
			// but should keep checks for all prerequisites being set.
			// FIXME: make this depend on flavor
			if ( $valueLName && $this->unitConverter != null ) {

				$newValue = $this->unitConverter->toStandardUnits( $value );

				if ( $newValue ) {
					if ( $newValue->equals( $value ) ) {
						$this->complexValueHelper->attachValueNode(
							$writer,
							$propertyValueNamespace,
							$propertyValueLName,
							$dataType,
							$value,
							true
						);

						// The unnormalize value is always its own normalization.
						$this->linkNormalizedValue( $valueLName, $valueLName );
					} else {
						$normLName = $this->addValueNode(
							$writer,
							$propertyValueNamespace,
							$propertyValueLName,
							$dataType,
							$newValue,
							true
						);

						// The normalized value is always its own normalization.
						$this->linkNormalizedValue( $normLName, $normLName );

						// Connect the normalized value to the unnormalized value
						$this->linkNormalizedValue( $valueLName, $normLName );
					}
				}
			}
		}
	}

	/**
	 * Connects a normalized value node to its base node via the quantityNormalized predicate.
	 *
	 * @param string $valueLName
	 * @param string $normLName
	 */
	private function linkNormalizedValue( $valueLName, $normLName ) {
		$valueWriter = $this->complexValueHelper->getValueNodeWriter();
		$valueWriter->about( RdfVocabulary::NS_VALUE, $valueLName )
			->say( RdfVocabulary::NS_ONTOLOGY, 'quantityNormalized' )
			->is( RdfVocabulary::NS_VALUE, $normLName );
	}

	/**
	 * Adds a value node representing all details of $value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param QuantityValue $value
	 * @param bool $normalized Is this a normalized value?
	 *
	 * @return string|null The LName of the value node (in the RdfVocabulary::NS_VALUE namespace),
	 *  or null if the value node should not be processed (generally, because it already has
	 *  been processed).
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

		// If the value node is already present in the output, don't create it again!
		if ( $valueLName !== null ) {
			$this->writeQuantityValue( $value );
		}

		return $valueLName;
	}

	/**
	 * Write data for the value.
	 * This expects the current subject of the RDF writer to be the value node.
	 * No instance-of statement is written about the value.
	 *
	 * @param QuantityValue $value
	 */
	public function writeQuantityValue( QuantityValue $value ) {
		$valueWriter = $this->complexValueHelper->getValueNodeWriter();

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityAmount' )
			->value( $value->getAmount(), 'xsd', 'decimal' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUpperBound' )
			->value( $value->getUpperBound(), 'xsd', 'decimal' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityLowerBound' )
			->value( $value->getLowerBound(), 'xsd', 'decimal' );

		$unitUri = trim( $value->getUnit() );

		if ( $unitUri === '1' ) {
			$unitUri = RdfVocabulary::ONE_ENTITY;
		}

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUnit' )->is( $unitUri );
	}

}
