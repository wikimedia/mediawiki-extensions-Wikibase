<?php

namespace Wikibase\Test\IO;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\IO\EntityIdReader;

/**
 * @covers Wikibase\IO\EntityIdReader
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
class EntityIdReaderTest extends PHPUnit_Framework_TestCase {

	protected function getTestFile() {
		return __DIR__ . '/EntityIdReaderTest.txt';
	}

	protected function openIdReader( $file ) {
		$handle = fopen( $file, 'r' );
		return new EntityIdReader( $handle );
	}

	public function testIteration() {
		$expected = array(
			new ItemId( 'Q1' ),
			new PropertyId( 'P2' ),
			new ItemId( 'Q3' ),
			new PropertyId( 'P4' ),
		);

		$file = $this->getTestFile();
		$reader = $this->openIdReader( $file );
		$actual = iterator_to_array( $reader );
		$reader->dispose();

		$this->assertEmpty( array_diff( $expected, $actual ), "Different IDs" );
	}

}
