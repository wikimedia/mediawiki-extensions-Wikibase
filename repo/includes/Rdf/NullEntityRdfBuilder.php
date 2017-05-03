<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Null implementation of EntityRdfBuilder
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class NullEntityRdfBuilder implements EntityRdfBuilder {

	/**
	 * @param EntityDocument $entity
	 */
	public function addEntity( EntityDocument $entity ) {
		return;
	}

	/**
	 * @param EntityDocument $entity
	 */
	public function addEntityStub( EntityDocument $entity ) {
		return;
	}

}
