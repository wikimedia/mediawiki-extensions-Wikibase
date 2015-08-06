<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use DataValues\TimeValue;
use Wikibase\Rdf\DataValueRdfBuilder;
use Wikibase\Rdf\DateTimeValueCleaner;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for TimeValues.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TimeRdfBuilder implements DataValueRdfBuilder {

	/**
	 * @var DateTimeValueCleaner
	 */
	private $dateCleaner;

	/**
	 * @param DateTimeValueCleaner $dateCleaner
	 */
	function __construct( DateTimeValueCleaner $dateCleaner ) {
		$this->dateCleaner = $dateCleaner;
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param TimeValue $value
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		DataValue $value
	) {
		$writer->say( $propertyValueNamespace, $propertyValueLName );

		/** @var TimeValue $value */
		$dateValue = $this->dateCleaner->getStandardValue( $value );

		if ( !is_null( $dateValue ) ) {
			$writer->value( $dateValue, 'xsd', 'dateTime' );
		} else {
			$writer->value( $value->getTime() );
		}
	}

}
