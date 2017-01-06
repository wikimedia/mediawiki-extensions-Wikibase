<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikimedia\Purtle\RdfWriter;

/**
 * Interface for RDF mapping for wikibase data values.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
interface ValueSnakRdfBuilder {

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
	);

}
