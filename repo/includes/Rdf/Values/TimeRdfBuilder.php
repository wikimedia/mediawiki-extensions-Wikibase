<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\TimeValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Rdf\DateTimeValueCleaner;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for TimeValues.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TimeRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * @var DateTimeValueCleaner
	 */
	private $dateCleaner;

	/**
	 * @var ComplexValueRdfHelper|null
	 */
	private $complexValueHelper;

	/**
	 * @param DateTimeValueCleaner $dateCleaner
	 * @param ComplexValueRdfHelper|null $complexValueHelper
	 */
	public function __construct(
		DateTimeValueCleaner $dateCleaner,
		ComplexValueRdfHelper $complexValueHelper = null
	) {
		$this->dateCleaner = $dateCleaner;
		$this->complexValueHelper = $complexValueHelper;
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
		$writer->say( $propertyValueNamespace, $propertyValueLName );

		/** @var TimeValue $value */
		$value = $snak->getDataValue();
		'@phan-var TimeValue $value';
		$this->sayDateLiteral( $writer, $value );

		if ( $this->complexValueHelper !== null ) {
			$this->addValueNode( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $snakNamespace, $value );
		}
	}

	private function sayDateLiteral( RdfWriter $writer, TimeValue $value ) {
		$dateValue = $this->dateCleaner->getStandardValue( $value );
		if ( $dateValue !== null ) {
			// XXX: type should perhaps depend on precision.
			$writer->value( $dateValue, 'xsd', 'dateTime' );
		} else {
			$writer->value( $value->getTime() );
		}
	}

	/**
	 * Adds a value node representing all details of $value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param string $snakNamespace
	 * @param TimeValue $value
	 */
	private function addValueNode(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		$snakNamespace,
		TimeValue $value
	) {
		$valueLName = $this->complexValueHelper->attachValueNode(
			$writer,
			$propertyValueNamespace,
			$propertyValueLName,
			$dataType,
			$snakNamespace,
			$value
		);

		if ( $valueLName === null ) {
			// The value node is already present in the output, don't create it again!
			return;
		}

		$valueWriter = $this->complexValueHelper->getValueNodeWriter();

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'timeValue' );
		$this->sayDateLiteral( $valueWriter, $value );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'timePrecision' )
			->value( $value->getPrecision(), 'xsd', 'integer' ); //TODO: use identifiers

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'timeTimezone' )
			->value( $value->getTimezone(), 'xsd', 'integer' ); //XXX: underspecified

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'timeCalendarModel' )
			->is( trim( $value->getCalendarModel() ) );
	}

}
