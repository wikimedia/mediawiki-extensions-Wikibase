<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\PlainEntityIdFormatter;

/**
 * @covers \Wikibase\DataModel\Services\EntityId\PlainEntityIdFormatter
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PlainEntityIdFormatterTest extends TestCase {

	public static function validProvider(): iterable {
		yield [ new ItemId( 'q42' ), 'Q42' ];
		yield [ new ItemId( 'q9001' ), 'Q9001' ];
		yield [ new ItemId( 'q1' ), 'Q1' ];

		yield [ new NumericPropertyId( 'p42' ), 'P42' ];
		yield [ new NumericPropertyId( 'p9001' ), 'P9001' ];
		yield [ new NumericPropertyId( 'p1' ), 'P1' ];
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId $entityId
	 * @param string $expectedString
	 */
	public function testParseWithValidArguments( EntityId $entityId, $expectedString ) {
		$formatter = new PlainEntityIdFormatter();

		$formattingResult = $formatter->formatEntityId( $entityId );

		$this->assertIsString( $formattingResult );
		$this->assertEquals( $expectedString, $formattingResult );
	}

}
