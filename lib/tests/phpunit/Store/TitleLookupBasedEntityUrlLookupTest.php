<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Title;
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

	public function testGetFullUrl() {
		$entityId = new ItemId( 'Q123' );
		$url = 'http://some-wikibase/wiki/Item:Q123';

		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getFullURL' )
			->willReturn( $url );

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $title );

		$this->assertSame(
			$url,
			( new TitleLookupBasedEntityUrlLookup( $titleLookup ) )
				->getFullUrl( $entityId )
		);
	}

}
