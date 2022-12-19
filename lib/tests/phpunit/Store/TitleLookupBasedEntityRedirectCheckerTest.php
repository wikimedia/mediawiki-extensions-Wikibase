<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityRedirectChecker;

/**
 * @covers \Wikibase\Lib\Store\TitleBasedEntityRedirectChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityRedirectCheckerTest extends TestCase {

	/**
	 * @dataProvider titleProvider
	 */
	public function testIsRedirect( $title, bool $isRedirect ) {
		$entityId = new ItemId( 'Q666' );

		$redirectChecker = new TitleLookupBasedEntityRedirectChecker( $this->newMockTitleLookup( $entityId, $title ) );

		$this->assertSame( $isRedirect, $redirectChecker->isRedirect( $entityId ) );
	}

	private function newMockTitleLookup( ItemId $entityId, $title ) {
		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $title );

		return $titleLookup;
	}

	public function titleProvider() {
		yield 'title not found' => [
			null, false,
		];
		yield 'title is not local' => [
			$this->newMockTitle( false, true ), false,
		];
		yield 'local title is not a redirect' => [
			$this->newMockTitle( true, false ), false,
		];
		yield 'title is a redirect' => [
			$this->newMockTitle( true, true ), true,
		];
	}

	private function newMockTitle( bool $isLocal, bool $isRedirect ) {
		$title = $this->createMock( Title::class );
		$title->method( 'isLocal' )
			->willReturn( $isLocal );
		$title->method( 'isRedirect' )
			->willReturn( $isRedirect );

		return $title;
	}

}
