<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Rdf\DataValueRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for DataValues that map to a resource (object) URI..
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ObjectValueRdfBuilder implements DataValueRdfBuilder {

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
		$uri = $this->getValueUri( $value );

		$writer->say( $propertyValueNamespace, $propertyValueLName )->is( $uri );
	}

	/**
	 * @param DataValue $value
	 *
	 * @return string the object URI
	 */
	protected function getValueUri( DataValue $value ) {
		return trim( $value->getValue() );
	}
}
