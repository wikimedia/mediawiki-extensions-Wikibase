<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Interface for an RDF mapping for wikibase entities. It's up to the implementation to decide which
 * aspects of the provided entities it will output to RDF.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
interface EntityRdfBuilder {

	/**
	 * Map some aspect of an Entity to the RDF graph.
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntity( EntityDocument $entity );

	/**
	 * Map some aspect of an Entity to the RDF graph, as it should appear in the stub
	 * representation of an entity.
	 *
	 * The implementation of this method will often be empty, since most aspects of an entity
	 * should not be included in the stub representation. Typically, the stub only contains
	 * basic type information and labels, for use by RDF modelling tools.
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntityStub( EntityDocument $entity );

}
