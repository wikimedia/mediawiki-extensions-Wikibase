<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\LegacyIdInterpreter;

/**
 * @covers Wikibase\DataModel\LegacyIdInterpreter
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Katie FIlbert < aude.wiki@gmail.com >
 */
class LegacyIdInterpreterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider idProvider
	 */
	public function testNewIdFromTypeAndNumber( EntityId $expected, $type, $number ) {
		$actual = LegacyIdInterpreter::newIdFromTypeAndNumber( $type, $number );

		$this->assertEquals( $actual, $expected );
	}

	public function idProvider() {
		return array(
			array( new ItemId( 'Q42' ), 'item', 42 ),
			array( new PropertyId( 'P42' ), 'property', 42 ),
		);
	}

	/**
	 * @dataProvider invalidInputProvider
	 */
	public function testNewIdFromTypeAndNumber_withInvalidInput( $type, $number ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		LegacyIdInterpreter::newIdFromTypeAndNumber( $type, $number );
	}

	public function invalidInputProvider() {
		return array(
			array( 'kittens', 42 ),
			array( 'item', array( 'kittens' ) ),
			array( 'item', true ),
		);
	}

}
