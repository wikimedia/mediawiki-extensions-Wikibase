<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Hooks\Helpers;

use OutputPage;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;

/**
 * @covers \Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class OutputPageEntityViewCheckerTest extends TestCase {

	private const ITEM_CONTENT_MODEL = 'wikibase-item';

	public function testGivenOutputPageIsEntityArticle_returnsTrue() {
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getContentModel' )
			->willReturn( self::ITEM_CONTENT_MODEL );

		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->once() )
			->method( 'isArticle' )
			->willReturn( true );
		$out->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		$entityContentFactory->expects( $this->once() )
			->method( 'isEntityContentModel' )
			->with( self::ITEM_CONTENT_MODEL )
			->willReturn( true );

		$entityViewChecker = new OutputPageEntityViewChecker( $entityContentFactory );

		$this->assertTrue( $entityViewChecker->hasEntityView( $out ) );
	}

	public function testGivenOutputPageHasEntityId_returnsTrue() {
		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->once() )
			->method( 'getJsConfigVars' )
			->willReturn( [ 'wbEntityId' => 'Q666' ] );

		$entityViewChecker = new OutputPageEntityViewChecker( $this->createMock( EntityContentFactory::class ) );

		$this->assertTrue( $entityViewChecker->hasEntityView( $out ) );
	}

	public function testGivenNotAnArticlePageAndNotHavingEntityId_returnsFalse() {
		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->once() )
			->method( 'isArticle' )
			->willReturn( true );
		$out->expects( $this->once() )
			->method( 'getJsConfigVars' )
			->willReturn( [] );

		$entityViewChecker = new OutputPageEntityViewChecker( $this->createMock( EntityContentFactory::class ) );

		$this->assertFalse( $entityViewChecker->hasEntityView( $out ) );
	}

}
