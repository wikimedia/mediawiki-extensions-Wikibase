<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\QuantityValue;
use DataValues\UnboundedQuantityValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for UnboundedQuantityValue and QuantityValue.
 *
 * @license GPL-2.0-or-later
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
		$snakNamespace,
		PropertyValueSnak $snak
	) {
		/** @var UnboundedQuantityValue $value */
		$value = $snak->getDataValue();
		'@phan-var UnboundedQuantityValue $value';
		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->value( $value->getAmount(), 'xsd', 'decimal' );
		//FIXME: this is meaningless without a unit identifier!

		if ( $this->complexValueHelper !== null ) {
			$valueLName = $this->addValueNode(
				$writer,
				$propertyValueNamespace,
				$propertyValueLName,
				$dataType,
				$snakNamespace,
				$value
			);

			// Can we convert units? This condition may become more complex in the future,
			// but should keep checks for all prerequisites being set.
			// FIXME: make this depend on flavor
			if ( $this->unitConverter ) {
				$newValue = $this->unitConverter->toStandardUnits( $value );

				if ( $newValue ) {
					$normLName = $this->addValueNode(
						$writer,
						$propertyValueNamespace,
						$propertyValueLName,
						$dataType,
						$snakNamespace,
						$newValue,
						true
					);
					if ( $newValue->equals( $value ) ) {
						// The normalized value is always its own normalization.
						$this->linkNormalizedValue( $snakNamespace, $valueLName, $valueLName );
					} else {
						// The normalized value is always its own normalization.
						$this->linkNormalizedValue( $snakNamespace, $normLName, $normLName );

						// Connect the normalized value to the unnormalized value
						$this->linkNormalizedValue( $snakNamespace, $valueLName, $normLName );
					}
				}
			}
		}
	}

	/**
	 * Connects a normalized value node to its base node via the quantityNormalized predicate.
	 *
	 * @param string|null $valueLName
	 * @param string|null $normLName
	 */
	private function linkNormalizedValue( $valueNamespace, $valueLName, $normLName ) {
		if ( $valueLName === null || $normLName === null ) {
			return;
		}
		$valueWriter = $this->complexValueHelper->getValueNodeWriter();
		$valueWriter->about( $valueNamespace, $valueLName )
			->say( RdfVocabulary::NS_ONTOLOGY, 'quantityNormalized' )
			->is( $valueNamespace, $normLName );
	}

	/**
	 * Adds a value node representing all details of $value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param UnboundedQuantityValue $value
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
		$valueNamespace,
		UnboundedQuantityValue $value,
		$normalized = false
	) {
		$valueLName = $this->complexValueHelper->attachValueNode(
			$writer,
			$propertyValueNamespace,
			$propertyValueLName,
			$dataType,
			$valueNamespace,
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
	 * @param UnboundedQuantityValue $value
	 */
	public function writeQuantityValue( UnboundedQuantityValue $value ) {
		$valueWriter = $this->complexValueHelper->getValueNodeWriter();

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityAmount' )
			->value( $value->getAmount(), 'xsd', 'decimal' );

		if ( $value instanceof QuantityValue ) {
			$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUpperBound' )
				->value( $value->getUpperBound(), 'xsd', 'decimal' );
			$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityLowerBound' )
				->value( $value->getLowerBound(), 'xsd', 'decimal' );
		}

		$unitUri = trim( $value->getUnit() );

		if ( $unitUri === '1' ) {
			$unitUri = RdfVocabulary::ONE_ENTITY;
		}

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUnit' )->is( $unitUri );
	}

}
