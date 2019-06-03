<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikibase\TermIndexItemTermStore;

/**
 * @covers \Wikibase\TermIndexItemTermStore
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermIndexItemTermStoreTest extends TestCase {

	use PHPUnit4And6Compat;

	/** @var ItemId */
	private $itemId;

	/** @var Fingerprint */
	private $fingerprint;

	protected function setUp() {
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
		$itemTermStore = new TermIndexItemTermStore( $termIndex );

		$itemTermStore->storeTerms( $this->itemId, $this->fingerprint );
	}

	public function testDeleteTerms() {
		$termIndex = $this->createMock( TermIndex::class );
		$termIndex->expects( $this->once() )
			->method( 'deleteTermsOfEntity' )
			->with( $this->itemId );
		$itemTermStore = new TermIndexItemTermStore( $termIndex );

		$itemTermStore->deleteTerms( $this->itemId );
	}

	public function testGetTerms() {
		$termIndex = $this->createMock( TermIndex::class );
		$termIndex->expects( $this->once() )
			->method( 'getTermsOfEntity' )
			->with(
				$this->itemId,
				[
					TermIndexEntry::TYPE_LABEL,
					TermIndexEntry::TYPE_DESCRIPTION,
					TermIndexEntry::TYPE_ALIAS,
				]
			)
			->willReturn( [
				new TermIndexEntry( [
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'a label',
					'entityId' => $this->itemId,
				] ),
				new TermIndexEntry( [
					'termType' => TermIndexEntry::TYPE_DESCRIPTION,
					'termLanguage' => 'en',
					'termText' => 'a description',
					'entityId' => $this->itemId,
				] ),
				new TermIndexEntry( [
					'termType' => TermIndexEntry::TYPE_ALIAS,
					'termLanguage' => 'en',
					'termText' => 'an alias',
					'entityId' => $this->itemId,
				] ),
				new TermIndexEntry( [
					'termType' => TermIndexEntry::TYPE_ALIAS,
					'termLanguage' => 'en',
					'termText' => 'another alias',
					'entityId' => $this->itemId,
				] ),
			] );
		$itemTermStore = new TermIndexItemTermStore( $termIndex );

		$fingerprint = $itemTermStore->getTerms( $this->itemId );

		$this->assertEquals( $this->fingerprint, $fingerprint );
	}

}
