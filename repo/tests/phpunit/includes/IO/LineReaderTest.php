<?php

namespace Wikibase\Test\IO;

use Wikibase\Repo\IO\LineReader;

/**
 * @covers Wikibase\Repo\IO\LineReader
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseIO
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class LineReaderTest extends \PHPUnit_Framework_TestCase {

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
