<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Null implementation of EntityRdfBuilder
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class NullEntityRdfBuilder implements EntityRdfBuilder {

	public function addEntity( EntityDocument $entity ) {
		return;
	}

	public function addEntityStub( EntityDocument $entity ) {
		return;
	}

}
