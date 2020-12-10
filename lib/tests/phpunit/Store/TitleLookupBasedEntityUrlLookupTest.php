<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityUrlLookup;

/**
 * @covers \Wikibase\Lib\Store\TitleLookupBasedEntityUrlLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityUrlLookupTest extends TestCase {

	private const Q123_URL = 'http://some-wikibase/wiki/Item:Q123';

	public function provideExpectedAndTitleOrNullForFullURL() {
		yield [ self::Q123_URL, $this->getMockTitle( 'getFullURL', self::Q123_URL ) ];
		yield [ null, null ];
	}

	/**
	 * @dataProvider provideExpectedAndTitleOrNullForFullURL
	 */
	public function testGetFullUrl( $expected, $titleOrNull ) {
		$entityId = new ItemId( 'Q123' );
		$titleLookup = $this->getMockTitleLookup( $entityId, $titleOrNull );

		$this->assertSame(
			$expected,
			( new TitleLookupBasedEntityUrlLookup( $titleLookup ) )
				->getFullUrl( $entityId )
		);
	}

	public function provideExpectedAndTitleOrNullForLinkURL() {
		yield [ self::Q123_URL, $this->getMockTitle( 'getLinkURL', self::Q123_URL ) ];
		yield [ null, null ];
	}

	/**
	 * @dataProvider provideExpectedAndTitleOrNullForLinkURL
	 */
	public function testGetLinkUrl( $expected, $titleOrNull ) {
		$entityId = new ItemId( 'Q123' );
		$titleLookup = $this->getMockTitleLookup( $entityId, $titleOrNull );

		$this->assertSame(
			$expected,
			( new TitleLookupBasedEntityUrlLookup( $titleLookup ) )
				->getLinkUrl( $entityId )
		);
	}

	private function getMockTitle( string $method, string $url ) {
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( $method )
			->willReturn( $url );
		return $title;
	}

	private function getMockTitleLookup( EntityId $entityId, $return ) {
		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $return );
		return $titleLookup;
	}

}
