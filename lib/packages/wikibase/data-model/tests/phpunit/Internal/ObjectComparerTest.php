<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Internal\ObjectComparer;

/**
 * @covers Wikibase\DataModel\Internal\ObjectComparer
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ObjectComparerTest extends \PHPUnit_Framework_TestCase {

	public static function provideDataEquals(){
		return array(
			//equals
			array( null, null, true ),
			array( array(), array(), true ),
			array( array( 'foo' ), array( 'foo' ), true ),
			array( array( 'bar' => 'foo' ), array( 'bar' => 'foo' ), true ),
			array( true, true, true ),
			array( 100, 100, true ),
			array( 'abc', 'abc', true ),
			array( new \Exception(), new \Exception(), true ),
			array( new \Exception( 'foo' ), new \Exception( 'foo' ), true ),
			//notequals
			array( array(), array( 'foo' ), false ),
			array( array( 'foo' ), array( 'foo2' ), false ),
			array( array( 'bar2' => 'foo' ), array( 'bar' => 'foo' ), false ),
			array( array( 'bar' => 'foo2' ), array( 'bar' => 'foo' ), false ),
			array( true, false, false ),
			array( 100, 101, false ),
			array( 'abc', 'abcc', false ),
			array( new \Exception(), null, false ),
			array( new \Exception( 'foo2' ), new \Exception( 'foo' ), true ),
			array( false, null, false ),
		);
	}

	/**
	 * @dataProvider provideDataEquals
	 */
	public function testDataEquals( $a, $b, $expected ){
		$comparer = new ObjectComparer();
		$result = $comparer->dataEquals( $a, $b );
		$this->assertEquals( $expected, $result );
	}

}