<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use DataValues\QuantityValue;
use Wikibase\Rdf\DataValueRdfBuilder;
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
	}

}
