<?php

namespace Wikibase\Test;

use Wikibase\RDF\XmlRdfEmitter;
use Wikibase\RDF\RdfEmitter;

/**
 * @covers Wikibase\RDF\XmlRdfEmitter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class XmlRdfEmitterTest extends RdfEmitterTestBase {

	protected function getFileSuffix() {
		return 'rdf';
	}

	/**
	 * @return RdfEmitter
	 */
	protected function newEmitter() {
		return new XmlRdfEmitter();
	}
}
