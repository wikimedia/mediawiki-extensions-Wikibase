<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\DelegatingEntityTermStoreWriter;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\MultiTermStoreWriter;
use Wikibase\TermStore\Implementations\InMemoryItemTermStore;
use Wikibase\TermStore\Implementations\InMemoryPropertyTermStore;
use Wikibase\TermStore\Implementations\ThrowingPropertyTermStore;

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
