<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Formatters;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
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
class NonExistingEntityIdHtmlBrokenLinkFormatterTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );
	}

	public function provideTestFormatEntityIdBrokenLink() {
		yield [
			new ItemId( 'Q1' ),
			'<a title="(red-link-title: someTitle)" href="http://someurl.com" class="new">Q1</a>'
			. '(word-separator)<span class="wb-entity-undefinedinfo">(parentheses: (somePrefix-item))</span>',
		];
		yield [
			new NumericPropertyId( 'P99' ),
			'<a title="(red-link-title: someTitle)" href="http://someurl.com" class="new">P99</a>'
			. '(word-separator)<span class="wb-entity-undefinedinfo">(parentheses: (somePrefix-property))</span>',
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
