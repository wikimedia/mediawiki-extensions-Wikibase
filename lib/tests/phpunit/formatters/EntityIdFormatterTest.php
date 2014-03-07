<?php

namespace Wikibase\Test;

use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdFormatter;

/**
 * @covers Wikibase\Lib\EntityIdFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 * @group EntityIdFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdFormatterTest extends \PHPUnit_Framework_TestCase {

	protected function newEntityIdFormatter() {
		$options = new FormatterOptions();
		return new EntityIdFormatter( $options );
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

		$formattingResult = $formatter->format( $entityId );

		$this->assertInternalType( 'string', $formattingResult );
		$this->assertEquals( $expectedString, $formattingResult );
	}

}
