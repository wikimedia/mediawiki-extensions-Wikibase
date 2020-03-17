<?php

namespace Wikibase\Repo\Tests\IO;

use Wikibase\Repo\IO\LineReader;

/**
 * @covers \Wikibase\Repo\IO\LineReader
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class LineReaderTest extends \PHPUnit\Framework\TestCase {

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

		$this->assertSame( [], array_diff( $expected, $actual ), "Different Lines" );
	}

}
