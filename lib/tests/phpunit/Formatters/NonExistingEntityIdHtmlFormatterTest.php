<?php

namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\NonExistingEntityIdHtmlFormatter;

/**
 * @covers Wikibase\Lib\NonExistingEntityIdHtmlFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class NonExistingEntityIdHtmlFormatterTest extends TestCase {
	use \PHPUnit4And6Compat;

	public function provideTestFormatEntityId() {
		return [
			[ new ItemId( 'Q1' ), 'Q1 <span class="wb-entity-undefinedinfo">(⧼somePrefix-item⧽)</span>' ],
			[ new PropertyId( 'P99' ), 'P99 <span class="wb-entity-undefinedinfo">(⧼somePrefix-property⧽)</span>' ],
		];
	}

	/**
	 * @dataProvider provideTestFormatEntityId
	 */
	public function testFormatEntityId( $entityId, $expected ) {
		$formatter = new NonExistingEntityIdHtmlFormatter( 'somePrefix-' );
		$result = $formatter->formatEntityId( $entityId );
		$this->assertSame( $expected, $result );
	}

}
