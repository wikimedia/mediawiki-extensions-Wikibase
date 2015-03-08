<?php

namespace Wikibase\Test;

use Wikibase\RDF\RdfEmitter;
use Wikibase\RDF\TurtleRdfEmitter;

/**
 * @covers Wikibase\RDF\TurtleRdfEmitter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TurtleRdfEmitterTest extends RdfEmitterTestBase {

	protected function getFileSuffix() {
		return 'ttl';
	}

	/**
	 * @return RdfEmitter
	 */
	protected function newEmitter() {
		return new TurtleRdfEmitter();
	}
}
