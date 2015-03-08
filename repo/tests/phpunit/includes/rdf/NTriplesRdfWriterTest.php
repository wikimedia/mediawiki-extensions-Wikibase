<?php

namespace Wikibase\Test;

use Wikibase\RDF\NTriplesRdfWriter;
use Wikibase\RDF\RdfWriter;

/**
 * @covers Wikibase\RDF\NTriplesRdfWriter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 * @group RdfWriter
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class NTriplesRdfWriterTest extends RdfWriterTestBase {

	protected function getFileSuffix() {
		return 'nt';
	}

	/**
	 * @return RdfWriter
	 */
	protected function newWriter() {
		return new NTriplesRdfWriter();
	}
}
