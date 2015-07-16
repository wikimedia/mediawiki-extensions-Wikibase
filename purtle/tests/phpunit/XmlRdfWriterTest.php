<?php

namespace Wikimedia\Purtle\Tests;

use Wikimedia\Purtle\XmlRdfWriter;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikimedia\Purtle\XmlRdfWriter
 *
 * @group Purtle
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
	protected function newWriter() {
		return new XmlRdfWriter();
	}

}
