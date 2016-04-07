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

	protected function newEntityIdFormatter() {
		return new PlainEntityIdFormatter();
	}

	/**
	 * @return array
	 */
	public function validProvider() {
		$argLists = array();

		$argLists[] = array( new ItemId( 'q42' ), 'Q42' );
		$argLists[] = array( new ItemId( 'q9001' ), 'Q9001' );
		$argLists[] = array( new ItemId( 'q1' ), 'Q1' );

		$argLists[] = array( new PropertyId( 'p42' ), 'P42' );
		$argLists[] = array( new PropertyId( 'p9001' ), 'P9001' );
		$argLists[] = array( new PropertyId( 'p1' ), 'P1' );

		return $argLists;
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId $entityId
	 * @param string $expectedString
	 */
	public function testParseWithValidArguments( EntityId $entityId, $expectedString ) {
		$formatter = $this->newEntityIdFormatter();

		$formattingResult = $formatter->formatEntityId( $entityId );

		$this->assertInternalType( 'string', $formattingResult );
		$this->assertEquals( $expectedString, $formattingResult );
	}

}
