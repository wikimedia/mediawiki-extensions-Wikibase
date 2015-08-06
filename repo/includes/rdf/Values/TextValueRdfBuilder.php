<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for DataValues that map to a literal.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TextValueRdfBuilder implements DataValueRdfBuilder {

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
		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->text( $value->getValue() );
	}

}
