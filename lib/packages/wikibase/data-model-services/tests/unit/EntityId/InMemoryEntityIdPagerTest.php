<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\InMemoryEntityIdPager;

/**
 * @covers \Wikibase\DataModel\Services\EntityId\InMemoryEntityIdPager
 *
 * @license GPL-2.0-or-later
 */
class InMemoryEntityIdPagerTest extends TestCase {

	public function testReturnsEmptyArrayWhenThereAreNoIds() {
		$this->assertSame(
			[],
			( new InMemoryEntityIdPager() )->fetchIds( 10 )
		);
	}

	public function testReturnsTheFirstIdsUpToLimit() {
		$pager = new InMemoryEntityIdPager(
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' ),
			new ItemId( 'Q4' ),
			new ItemId( 'Q5' )
		);

		$this->assertEquals(
			[
				new ItemId( 'Q1' ),
				new ItemId( 'Q2' ),
			],
			$pager->fetchIds( 2 )
		);
	}

	public function testReturnsLessItemsIfThereAreFew() {
		$pager = new InMemoryEntityIdPager(
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' )
		);

		$this->assertEquals(
			[
				new ItemId( 'Q1' ),
				new ItemId( 'Q2' ),
			],
			$pager->fetchIds( 5 )
		);
	}

	public function testReturnsNextBatch() {
		$pager = new InMemoryEntityIdPager(
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' ),
			new ItemId( 'Q4' ),
			new ItemId( 'Q5' )
		);

		$pager->fetchIds( 2 );

		$this->assertEquals(
			[
				new ItemId( 'Q3' ),
				new ItemId( 'Q4' ),
			],
			$pager->fetchIds( 2 )
		);
	}

	public function testCanResumeFromPosition() {
		$firstPager = new InMemoryEntityIdPager(
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' ),
			new ItemId( 'Q4' ),
			new ItemId( 'Q5' )
		);

		$secondPager = new InMemoryEntityIdPager(
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' ),
			new ItemId( 'Q4' ),
			new ItemId( 'Q5' )
		);

		$firstPager->fetchIds( 2 );
		$secondPager->setPosition( $firstPager->getPosition() );

		$this->assertEquals(
			[
				new ItemId( 'Q3' ),
				new ItemId( 'Q4' ),
			],
			$secondPager->fetchIds( 2 )
		);
	}

	public function testCanAddIds() {
		$pager = new InMemoryEntityIdPager(
			new ItemId( 'Q1' )
		);

		$pager->addEntityId( new ItemId( 'Q2' ) );

		$this->assertEquals(
			[
				new ItemId( 'Q1' ),
				new ItemId( 'Q2' ),
			],
			$pager->fetchIds( 2 )
		);
	}

}
