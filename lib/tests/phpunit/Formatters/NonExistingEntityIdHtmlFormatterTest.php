<?php

namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class NonExistingEntityIdHtmlFormatterTest extends TestCase {

	public function provideTestFormatEntityId() {
		yield [ new ItemId( 'Q1' ), 'Q1 <span class="wb-entity-undefinedinfo">(⧼somePrefix-item⧽)</span>' ];
		yield [ new NumericPropertyId( 'P99' ), 'P99 <span class="wb-entity-undefinedinfo">(⧼somePrefix-property⧽)</span>' ];
	}

	/**
	 * @dataProvider provideTestFormatEntityId
	 */
	public function testFormatEntityId( EntityId $entityId, $expected ) {
		$formatter = new NonExistingEntityIdHtmlFormatter( 'somePrefix-' );
		$result = $formatter->formatEntityId( $entityId );

		$this->assertSame( $expected, $result );
	}

}
