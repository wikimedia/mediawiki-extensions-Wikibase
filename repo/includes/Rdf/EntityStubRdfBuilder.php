<?php

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for an RDF mapping for parts of a Wikibase Entity by id
 *
 * @license GPL-2.0-or-later
 */
interface EntityStubRdfBuilder {

	/**
	 * Map some aspect of an Entity to the RDF graph, as it should appear in the stub
	 * representation of an entity.
	 *
	 * @param EntityId $id the entity that the rdfbuilder will add stub data to the graph of.
	 */
	public function addEntityStub( EntityId $id );

}
