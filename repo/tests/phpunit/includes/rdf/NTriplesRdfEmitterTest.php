<?php

namespace Wikibase\Test;

use Wikibase\RDF\NTriplesRdfEmitter;
use Wikibase\RDF\RdfEmitter;

/**
 * @covers Wikibase\RDF\NTriplesRdfEmitter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class NTriplesRdfEmitterTest extends RdfEmitterTestBase {

	protected function getFileSuffix() {
		return 'nt';
	}

	/**
	 * @return RdfEmitter
	 */
	protected function newEmitter() {
		return new NTriplesRdfEmitter();
	}
}
