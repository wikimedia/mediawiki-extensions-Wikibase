<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
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
	public function testIsRedirect( callable $titleFactory, bool $isRedirect ) {
		$entityId = new ItemId( 'Q666' );

		$redirectChecker = new TitleLookupBasedEntityRedirectChecker( $this->newMockTitleLookup( $entityId, $titleFactory( $this ) ) );

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

	public static function titleProvider() {
		yield 'title not found' => [
			fn () => null, false,
		];
		yield 'title is not local' => [
			fn ( self $self ) => $self->newMockTitle( false, true ), false,
		];
		yield 'local title is not a redirect' => [
			fn ( self $self ) => $self->newMockTitle( true, false ), false,
		];
		yield 'title is a redirect' => [
			fn ( self $self ) => $self->newMockTitle( true, true ), true,
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
