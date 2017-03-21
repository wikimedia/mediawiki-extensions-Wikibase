<?php

namespace Wikibase\Repo\Rdf\Values;

use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class GeoShapeRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace
	 * @param string $propertyValueLName
	 * @param string $dataType
	 * @param PropertyValueSnak $snak
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		PropertyValueSnak $snak
	) {
		// TODO: Implement proper RDF mapping, see T159517 and T160535
	}

}
