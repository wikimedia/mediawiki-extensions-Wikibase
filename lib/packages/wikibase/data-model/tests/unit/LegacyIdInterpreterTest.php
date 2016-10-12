<?php

namespace Wikibase\DataModel\Tests;

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
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
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
		return [
			[ new ItemId( 'Q42' ), 'item', 42 ],
			[ new PropertyId( 'P42' ), 'property', 42 ],
		];
	}

	/**
	 * @dataProvider invalidInputProvider
	 */
	public function testNewIdFromTypeAndNumber_withInvalidInput( $type, $number ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		LegacyIdInterpreter::newIdFromTypeAndNumber( $type, $number );
	}

	public function invalidInputProvider() {
		return [
			[ 'kittens', 42 ],
			[ 'item', [ 'kittens' ] ],
			[ 'item', true ],
		];
	}

}
