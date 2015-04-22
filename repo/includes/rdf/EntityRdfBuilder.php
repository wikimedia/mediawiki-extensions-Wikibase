<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Interface for an RDF mapping for (some aspect of) wikibase entities.
 * FIXME: What exactly does "some" mean?
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
