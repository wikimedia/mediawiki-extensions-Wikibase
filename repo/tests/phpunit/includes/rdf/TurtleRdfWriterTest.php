<?php

namespace Wikibase\Test;

use Wikibase\RDF\RdfWriter;
use Wikibase\RDF\TurtleRdfWriter;

/**
 * @covers Wikibase\RDF\TurtleRdfWriter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 * @group RdfWriter
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TurtleRdfWriterTest extends RdfWriterTestBase {

	protected function getFileSuffix() {
		return 'ttl';
	}

	/**
	 * @return RdfWriter
	 */
	protected function newWriter() {
		return new TurtleRdfWriter();
	}
}
