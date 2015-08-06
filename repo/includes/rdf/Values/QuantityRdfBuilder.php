<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use DataValues\QuantityValue;
use Wikibase\Rdf\DataValueRdfBuilder;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for QuantityValue.
 *
 * @todo: FIXME: test me!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class QuantityRdfBuilder implements DataValueRdfBuilder {

	/**
	 * @var ComplexValueRdfHelper|null
	 */
	private $complexValueHelper;

	/**
	 * @param ComplexValueRdfHelper|null $complexValueHelper
	 */
	function __construct( ComplexValueRdfHelper $complexValueHelper = null ) {
		$this->complexValueHelper = $complexValueHelper;
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param QuantityValue $value
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		DataValue $value
	) {
		/** @var QuantityValue $value */
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
	 * @todo FIXME test me
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param QuantityValue $value
	 */
	private function addValueNode(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		QuantityValue $value
	) {
		$valueLName = $this->complexValueHelper->attachValueNode(
			$writer,
			$propertyValueNamespace,
			$propertyValueLName,
			$dataType,
			$value
		);

		if ( $valueLName === null ) {
			// The value node is already present in the output, don't create it again!
			return;
		}

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

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUnit' )
			->is( $unitUri );
	}

}
