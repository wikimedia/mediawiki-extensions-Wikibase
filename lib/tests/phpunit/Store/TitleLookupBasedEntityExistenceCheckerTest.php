<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityExistenceChecker;

/**
 * @covers \Wikibase\Lib\Store\TitleBasedEntityExistenceChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityExistenceCheckerTest extends TestCase {

	/**
	 * @dataProvider boolProvider
	 */
	public function testIsDeleted( bool $isKnown ) {
		$entityId = new ItemId( 'Q123' );

		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'isKnown' )
			->willReturn( $isKnown );

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $title );

		$this->assertSame(
			!$isKnown,
			( new TitleLookupBasedEntityExistenceChecker( $titleLookup ) )
				->isDeleted( $entityId )
		);
	}

	public function boolProvider() {
		return [ [ true ], [ false ] ];
	}

}
