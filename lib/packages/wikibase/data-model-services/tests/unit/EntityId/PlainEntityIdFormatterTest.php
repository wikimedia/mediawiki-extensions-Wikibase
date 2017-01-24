<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\PlainEntityIdFormatter;

/**
 * @covers Wikibase\DataModel\Services\EntityId\PlainEntityIdFormatter
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PlainEntityIdFormatterTest extends PHPUnit_Framework_TestCase {

	public function validProvider() {
		$argLists = [];

		$argLists[] = [ new ItemId( 'q42' ), 'Q42' ];
		$argLists[] = [ new ItemId( 'q9001' ), 'Q9001' ];
		$argLists[] = [ new ItemId( 'q1' ), 'Q1' ];

		$argLists[] = [ new PropertyId( 'p42' ), 'P42' ];
		$argLists[] = [ new PropertyId( 'p9001' ), 'P9001' ];
		$argLists[] = [ new PropertyId( 'p1' ), 'P1' ];

		return $argLists;
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

		$this->assertInternalType( 'string', $formattingResult );
		$this->assertEquals( $expectedString, $formattingResult );
	}

}
