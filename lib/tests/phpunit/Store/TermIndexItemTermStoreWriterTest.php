<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\TermIndex;
use Wikibase\Lib\Store\TermIndexItemTermStoreWriter;

/**
 * @covers \Wikibase\Lib\Store\TermIndexItemTermStoreWriter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermIndexItemTermStoreWriterTest extends TestCase {

	/** @var ItemId */
	private $itemId;

	/** @var Fingerprint */
	private $fingerprint;

	protected function setUp() : void {
		parent::setUp();
		$this->itemId = new ItemId( 'Q1' );
		$this->fingerprint = new Fingerprint(
			new TermList( [ new Term( 'en', 'a label' ) ] ),
			new TermList( [ new Term( 'en', 'a description' ) ] ),
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'an alias', 'another alias' ] )
			] )
		);
	}

	public function testStoreTerms() {
		$termIndex = $this->createMock( TermIndex::class );
		$termIndex->expects( $this->once() )
			->method( 'saveTermsOfEntity' )
			->with( $this->callback(
				function ( Item $item ) {
					$this->assertSame( $this->itemId, $item->getId() );
					$this->assertSame( $this->fingerprint, $item->getFingerprint() );
					return true;
				}
			) );
		$itemTermStoreWriter = new TermIndexItemTermStoreWriter( $termIndex );

		$itemTermStoreWriter->storeTerms( $this->itemId, $this->fingerprint );
	}

	public function testDeleteTerms() {
		$termIndex = $this->createMock( TermIndex::class );
		$termIndex->expects( $this->once() )
			->method( 'deleteTermsOfEntity' )
			->with( $this->itemId );
		$itemTermStoreWriter = new TermIndexItemTermStoreWriter( $termIndex );

		$itemTermStoreWriter->deleteTerms( $this->itemId );
	}

}
