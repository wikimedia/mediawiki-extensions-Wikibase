<?php

namespace Wikimedia\Purtle\Tests;

use Wikimedia\Purtle\RdfWriter;
use Wikimedia\Purtle\TurtleRdfWriter;

/**
 * @covers Wikimedia\Purtle\TurtleRdfWriter
 * @covers Wikimedia\Purtle\N3RdfWriterBase
 * @covers Wikimedia\Purtle\RdfWriterBase
 *
 * @group Purtle
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
