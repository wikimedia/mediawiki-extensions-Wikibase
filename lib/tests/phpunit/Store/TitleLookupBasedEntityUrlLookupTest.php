<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Store;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
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

	public static function provideExpectedAndTitleOrNullForFullURL() {
		yield [ self::Q123_URL, fn ( self $self ) => $self->getMockTitle( 'getFullURL', self::Q123_URL ) ];
		yield [ null, fn () => null ];
	}

	/**
	 * @dataProvider provideExpectedAndTitleOrNullForFullURL
	 */
	public function testGetFullUrl( $expected, callable $titleOrNullFactory ) {
		$entityId = new ItemId( 'Q123' );
		$titleLookup = $this->getMockTitleLookup( $entityId, $titleOrNullFactory( $this ) );

		$this->assertSame(
			$expected,
			( new TitleLookupBasedEntityUrlLookup( $titleLookup ) )
				->getFullUrl( $entityId )
		);
	}

	public static function provideExpectedAndTitleOrNullForLinkURL() {
		yield [ self::Q123_URL, fn ( self $self ) => $self->getMockTitle( 'getLinkURL', self::Q123_URL ) ];
		yield [ null, fn () => null ];
	}

	/**
	 * @dataProvider provideExpectedAndTitleOrNullForLinkURL
	 */
	public function testGetLinkUrl( $expected, $titleOrNullFactory ) {
		$entityId = new ItemId( 'Q123' );
		$titleLookup = $this->getMockTitleLookup( $entityId, $titleOrNullFactory( $this ) );

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
