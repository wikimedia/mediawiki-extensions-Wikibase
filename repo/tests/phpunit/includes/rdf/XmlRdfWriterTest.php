<?php

namespace Wikibase\Test;

use Wikibase\RDF\XmlRdfWriter;
use Wikibase\RDF\RdfWriter;

/**
 * @covers Wikibase\RDF\XmlRdfWriter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 * @group RdfWriter
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class XmlRdfWriterTest extends RdfWriterTestBase {

	protected function getFileSuffix() {
		return 'rdf';
	}

	/**
	 * @return RdfWriter
	 */
	protected function newWriter( $role = XmlRdfWriter::DOCUMENT_ROLE ) {
		return new XmlRdfWriter( $role );
	}
}
