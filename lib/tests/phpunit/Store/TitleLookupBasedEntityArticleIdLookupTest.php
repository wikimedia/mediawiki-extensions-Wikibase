<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityArticleIdLookup;

/**
 * @covers \Wikibase\Lib\Store\TitleLookupBasedEntityArticleIdLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityArticleIdLookupTest extends TestCase {

	public function testGetArticleId() {
		$entityId = new ItemId( 'Q123' );
		$articleId = 42;

		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getArticleID' )
			->willReturn( $articleId );

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $title );

		$this->assertSame(
			$articleId,
			( new TitleLookupBasedEntityArticleIdLookup( $titleLookup ) )
				->getArticleId( $entityId )
		);
	}

}
