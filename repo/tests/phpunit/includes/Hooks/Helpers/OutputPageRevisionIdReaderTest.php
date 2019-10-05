<?php

namespace Wikibase\Repo\Tests\Hooks\Helpers;

use OutputPage;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\Repo\Hooks\Helpers\OutputPageRevisionIdReader;

/**
 * @covers \Wikibase\Repo\Hooks\Helpers\OutputPageRevisionIdReader
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class OutputPageRevisionIdReaderTest extends TestCase {

	public function testGetOutputPageWithRevId_returnsOutputPageRevId() {
		$reader = new OutputPageRevisionIdReader();

		$revision = 4711;

		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->once() )
			->method( 'getRevisionId' )
			->willReturn( $revision );
		$out->expects( $this->never() )
			->method( 'getTitle' );

		$this->assertSame( $revision, $reader->getRevisionFromOutputPage( $out ) );
	}

	public function testGetOutputPageWithNullOutputPageRevId_returnsTitleRevId() {
		$reader = new OutputPageRevisionIdReader();

		$revision = 4711;

		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( $revision );

		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->once() )
			->method( 'getRevisionId' )
			->willReturn( null );
		$out->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->assertSame( $revision, $reader->getRevisionFromOutputPage( $out ) );
	}

}
