<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlBrokenLinkFormatter;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;

/**
 * @covers \Wikibase\Lib\Formatters\NonExistingEntityIdHtmlBrokenLinkFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class NonExistingEntityIdHtmlBrokenLinkFormatterTest extends TestCase {

	public function provideTestFormatEntityIdBrokenLink() {
		yield [
			new ItemId( 'Q1' ),
			'<a title="someTitle (page does not exist)" href="http://someurl.com" class="new">'
			. 'Q1 <span class="wb-entity-undefinedinfo">(⧼somePrefix-item⧽)</span></a>'
		];
		yield [
			new PropertyId( 'P99' ),
			'<a title="someTitle (page does not exist)" href="http://someurl.com" class="new">'
			. 'P99 <span class="wb-entity-undefinedinfo">(⧼somePrefix-property⧽)</span></a>'
		];
	}

	/**
	 * @dataProvider provideTestFormatEntityIdBrokenLink
	 */
	public function testFormatEntityId( EntityId $entityId, $expected ) {
		$formatter = new NonExistingEntityIdHtmlBrokenLinkFormatter(
			'somePrefix-',
			$this->getEntityTitleTextLookupMock(),
			$this->getEntityUrlLookupMock()
		);
		$result = $formatter->formatEntityId( $entityId );

		$this->assertSame( $expected, $result );
	}

	private function getEntityTitleTextLookupMock() {
		$entityTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );
		$entityTitleTextLookup->method( 'getPrefixedText' )
			->willReturn( 'someTitle' );
		return $entityTitleTextLookup;
	}

	private function getEntityUrlLookupMock() {
		$entityUrlLookup = $this->createMock( EntityUrlLookup::class );
		$entityUrlLookup->method( 'getLinkUrl' )
			->willReturn( 'http://someurl.com' );
		return $entityUrlLookup;
	}
}
