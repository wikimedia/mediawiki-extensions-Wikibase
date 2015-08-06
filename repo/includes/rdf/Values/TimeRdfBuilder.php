<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use Wikimedia\Assert\Assert;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for time DataValues.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TimeRdfBuilder extends LiteralValueRdfBuilder {

	function __construct() {
		parent::__construct( 'xsd', 'dateTime' );
	}


	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param DataValue $value
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		DataValue $value
	) {
		$literalValue = $this->getLiteralValue( $value );
		$nsType = $this->typeBase ?: ( $this->typeLocal === null ? null : 'xsd' );

		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->value( $literalValue, $nsType, $this->typeLocal );
	}

	/**
	 * @param DataValue $value
	 *
	 * @return string
	 */
	protected function getLiteralValue( DataValue $value ) {
		return $value->getValue();
	}
}
