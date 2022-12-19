<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Store;

use MediaWiki\Cache\LinkBatchFactory;
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
	 * @dataProvider existenceProvider
	 */
	public function testExists( bool $isNull, bool $isKnown, bool $expected ) {
		$entityId = new ItemId( 'Q123' );

		$mockTitle = $this->createMock( Title::class );
		$mockTitle->expects( $this->atMost( 1 ) )
			->method( 'isKnown' )
			->willReturn( $isKnown );

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $isNull ? null : $mockTitle );

		$linkBatchFactory = $this->createMock( LinkBatchFactory::class );
		$linkBatchFactory->expects( $this->never() )
			->method( 'newLinkBatch' );

		$result = ( new TitleLookupBasedEntityExistenceChecker( $titleLookup, $linkBatchFactory ) )
			->exists( $entityId );
		$this->assertSame(
			$expected,
			$result
		);
	}

	public function existenceProvider() {
		return [
			'title is null' => [
				'isNull' => true,
				'isKnown' => false,
				'expected' => false,
			],
			'title is not null and is known' => [
				'isNull' => false,
				'isKnown' => true,
				'expected' => true,
			],
			'title is not null and not known' => [
				'isNull' => false,
				'isKnown' => false,
				'expected' => false,
			],
		];
	}

	public function testExistsBatch() {
		$ids = [
			new ItemId( 'Q123' ),
			new ItemId( 'Q456' ),
			new ItemId( 'Q789' ),
		];

		$title1 = $this->createMock( Title::class );
		$title1->expects( $this->once() )
			->method( 'isKnown' )
			->willReturn( true );
		$title2 = $this->createMock( Title::class );
		$title2->expects( $this->once() )
			->method( 'isKnown' )
			->willReturn( false );
		$title3 = null;

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitlesForIds' )
			->with( $ids )
			->willReturn( [
				'Q123' => $title1,
				'Q456' => $title2,
				'Q789' => $title3,
			] );

		$linkBatchFactory = $this->createMock( LinkBatchFactory::class );
		$linkBatchFactory->expects( $this->once() )
			->method( 'newLinkBatch' )
			->with( [
				'Q123' => $title1,
				'Q456' => $title2,
				// no $title3
			] );

		$result = ( new TitleLookupBasedEntityExistenceChecker( $titleLookup, $linkBatchFactory ) )
			->existsBatch( $ids );

		$expected = [
			'Q123' => true,
			'Q456' => false,
			'Q789' => false,
		];
		$this->assertSame( $expected, $result );
	}

}
