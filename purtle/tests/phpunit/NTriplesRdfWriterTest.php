<?php

namespace Wikimedia\Purtle\Tests;

use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikimedia\Purtle\NTriplesRdfWriter
 *
 * @group Purtle
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
