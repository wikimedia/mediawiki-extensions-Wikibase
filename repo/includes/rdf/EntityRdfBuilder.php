<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Interface for an RDF mapping for wikibase entities. It's up to the implementation to decide which
 * aspects of the provided entities it will output to RDF.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
interface EntityRdfBuilder {

	/**
	 * Add an entity to the RDF graph, including all supported structural components
	 * of the entity.
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntity( EntityDocument $entity );

}
