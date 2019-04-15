<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\MultiTermStoreWriter;

/**
 * @covers \Wikibase\Lib\Store\MultiTermStoreWriter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultiTermStoreWriterTest extends TestCase {
	use PHPUnit4And6Compat;

	public function testWritesToBothStores() {
		$entity = new Item();

		$oldStore = $this->createMock( EntityTermStoreWriter::class );
		$newStore = $this->createMock( EntityTermStoreWriter::class );

		$oldStore->expects( $this->once() )
			->method( 'saveTermsOfEntity' )
			->with( $this->equalTo( $entity ) )
			->willReturn( true );

		$newStore->expects( $this->once() )
			->method( 'saveTermsOfEntity' )
			->with( $this->equalTo( $entity ) )
			->willReturn( true );

		$multiStore = new MultiTermStoreWriter( $oldStore, $newStore );
		$this->assertTrue( $multiStore->saveTermsOfEntity( $entity ) );
	}

	public function testDeletesFromBothStores() {
		$entityId = new ItemId( 'Q1' );

		$oldStore = $this->createMock( EntityTermStoreWriter::class );
		$newStore = $this->createMock( EntityTermStoreWriter::class );

		$oldStore->expects( $this->once() )
			->method( 'deleteTermsOfEntity' )
			->with( $this->equalTo( $entityId ) )
			->willReturn( true );

		$newStore->expects( $this->once() )
			->method( 'deleteTermsOfEntity' )
			->with( $this->equalTo( $entityId ) )
			->willReturn( true );

		$multiStore = new MultiTermStoreWriter( $oldStore, $newStore );
		$this->assertTrue( $multiStore->deleteTermsOfEntity( $entityId ) );
	}

}
