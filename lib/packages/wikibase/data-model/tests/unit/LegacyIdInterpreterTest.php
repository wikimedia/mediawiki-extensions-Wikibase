<?php

namespace Wikibase\DataModel\Tests;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\LegacyIdInterpreter;

/**
 * @covers \Wikibase\DataModel\LegacyIdInterpreter
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LegacyIdInterpreterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider idProvider
	 */
	public function testNewIdFromTypeAndNumber( EntityId $expected, $type, $number ) {
		$actual = LegacyIdInterpreter::newIdFromTypeAndNumber( $type, $number );

		$this->assertEquals( $expected, $actual );
	}

	public function idProvider() {
		return [
			[ new ItemId( 'Q42' ), 'item', 42 ],
			[ new NumericPropertyId( 'P42' ), 'property', 42 ],
		];
	}

	/**
	 * @dataProvider invalidInputProvider
	 */
	public function testNewIdFromTypeAndNumber_withInvalidInput( $type, $number ) {
		$this->expectException( InvalidArgumentException::class );
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
