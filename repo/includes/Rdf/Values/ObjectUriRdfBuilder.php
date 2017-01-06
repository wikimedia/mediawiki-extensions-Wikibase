<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for DataValues that map to a resource (object) URI.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ObjectUriRdfBuilder implements ValueSnakRdfBuilder {

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
		$uri = $this->getValueUri( $snak->getDataValue() );

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
