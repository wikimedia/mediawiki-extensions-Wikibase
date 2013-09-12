<?php

namespace Wikibase\Test\IO;
use PHPUnit_Framework_TestCase;
use Wikibase\IO\LineReader;

/**
 * @covers Wikibase\IO\LineReader
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseIO
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class LineReaderTest extends PHPUnit_Framework_TestCase {

	protected function getTestFile() {
		return __DIR__ . '/LineReaderTest.txt';
	}

	protected function openLineReader( $file ) {
		$handle = fopen( $file, 'r' );
		return new LineReader( $handle );
	}

	public function testIteration() {
		$file = $this->getTestFile();

		$expected = file( $file );

		$reader = $this->openLineReader( $file );
		$actual = iterator_to_array( $reader );
		$reader->dispose();

		$this->assertEmpty( array_diff( $expected, $actual ), "Different Lines" );
	}

}
