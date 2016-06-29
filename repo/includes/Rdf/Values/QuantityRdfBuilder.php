<?php

namespace Wikibase\Rdf\Values;

use DataValues\QuantityValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\UnitConvertor;
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
	 * @var UnitConvertor
	 */
	private $unitConvertor;

	/**
	 * @param ComplexValueRdfHelper|null $complexValueHelper
	 */
	public function __construct( ComplexValueRdfHelper $complexValueHelper = null, UnitConvertor $uc = null ) {
		$this->complexValueHelper = $complexValueHelper;
		$this->unitConvertor = $uc;
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

		if($this->unitConvertor && $unitUri != RdfVocabulary::ONE_ENTITY) {
			$newValue = $this->unitConvertor->toStandardUnits( $value );
			$valueStandardLName = $this->complexValueHelper->attachValueNode(
				$writer,
				RdfVocabulary::NS_ONTOLOGY,
				'quantityStandard',
				$dataType,
				$newValue
			);
//			if ( $valueStandardLName === null ) {
//				$valueStandardWriter = $this->complexValueHelper->getValueNodeWriter();
//			}
		}
	}

}
