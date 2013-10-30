<?php

namespace Wikibase\Test\IO;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\IO\EntityIdReader;

/**
 * @covers Wikibase\IO\EntityIdReader
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseIO
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityIdReaderTest extends \PHPUnit_Framework_TestCase {

	protected function getTestFile() {
		return __DIR__ . '/EntityIdReaderTest.txt';
	}

	protected function openIdReader( $file ) {
		$path = __DIR__ . '/' . $file;
		$handle = fopen( $path, 'r' );
		return new EntityIdReader( $handle );
	}

	public function testIteration() {
		$expected = array(
			new ItemId( 'Q1' ),
			new PropertyId( 'P2' ),
			new ItemId( 'Q3' ),
			new PropertyId( 'P4' ),
		);

		$reader = $this->openIdReader( 'EntityIdReaderTest.txt' );
		$actual = iterator_to_array( $reader );
		$reader->dispose();

		$this->assertEmpty( array_diff( $expected, $actual ), "Different IDs" );
	}

	public function testIterationWithErrors() {
		$expected = array(
			new ItemId( 'Q23' ),
			new PropertyId( 'P42' ),
		);

		$exceptionHandler = $this->getMock( 'ExceptionHandler' );
		$exceptionHandler->expects( $this->exactly( 2 ) ) //two bad lines in EntityIdReaderTest.bad.txt
			->method( 'handleException' );

		$reader = $this->openIdReader( 'EntityIdReaderTest.bad.txt' );
		$reader->setExceptionHandler( $exceptionHandler );

		$actual = iterator_to_array( $reader );
		$reader->dispose();

		$this->assertEmpty( array_diff( $expected, $actual ), "Different IDs" );
	}

}
