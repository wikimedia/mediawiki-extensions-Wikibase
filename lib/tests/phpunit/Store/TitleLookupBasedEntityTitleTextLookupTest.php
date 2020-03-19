<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityTitleTextLookup;

/**
 * @covers \Wikibase\Lib\Store\TitleLookupBasedEntityTitleTextLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityTitleTextLookupTest extends TestCase {

	public function testGetPrefixedText() {
		$entityId = new ItemId( 'Q123' );
		$titleText = 'Item:Q123';

		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getPrefixedText' )
			->willReturn( $titleText );

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $title );

		$this->assertSame(
			$titleText,
			( new TitleLookupBasedEntityTitleTextLookup( $titleLookup ) )
				->getPrefixedText( $entityId )
		);
	}

}
