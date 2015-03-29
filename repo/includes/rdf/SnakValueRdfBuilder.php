<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Purtle\RdfWriter;

/**
 * Interface for RDF mapping for wikibase data values.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
interface SnakValueRdfBuilder {

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param RdfWriter $writer entity-level writer
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 * @param string $propertyNamespace The property namespace for this snak
	 *
	 * @return
	 */
	public function addSnakValue(
		RdfWriter $writer,
		PropertyId $propertyId,
		DataValue $value,
		$propertyNamespace
	);

}
