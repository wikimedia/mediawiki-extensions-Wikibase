<?php

namespace Wikibase\Lib\Tests\Store;

use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\MultiItemTermStore;
use Wikibase\TermStore\ItemTermStore;

/**
 * @covers \Wikibase\MultiItemTermStore
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultiItemTermStoreTest extends TestCase {

	use PHPUnit4And6Compat;

	/** @var ItemId */
	private $itemId;

	/** @var Fingerprint */
	private $fingerprint;

	protected function setUp() {
		parent::setUp();
		$this->itemId = new ItemId( 'P1' );
		$this->fingerprint = new Fingerprint(
			new TermList( [ new Term( 'en', 'a label' ) ] ),
			new TermList( [ new Term( 'en', 'a description' ) ] ),
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'an alias', 'another alias' ] )
			] )
		);
	}

	public function testStoreTerms_success() {
		$itemTermStores = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$itemTermStore = $this->createMock( ItemTermStore::class );
			$itemTermStore->expects( $this->once() )
				->method( 'storeTerms' )
				->with( $this->itemId, $this->fingerprint );
			$itemTermStores[] = $itemTermStore;
		}

		( new MultiItemTermStore( $itemTermStores ) )
			->storeTerms( $this->itemId, $this->fingerprint );
	}

	public function testStoreTerms_oneException() {
		$exception = new Exception( __METHOD__ . ' exception' );
		$store1 = $this->createMock( ItemTermStore::class );
		$store1->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->itemId, $this->fingerprint )
			->willThrowException( $exception );
		$store2 = $this->createMock( ItemTermStore::class );
		$store2->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->itemId, $this->fingerprint );

		$this->setExpectedException( 'Exception', $exception->getMessage() );
		( new MultiItemTermStore( [ $store1, $store2 ] ) )
			->storeTerms( $this->itemId, $this->fingerprint );
	}

	public function testStoreTerms_allExceptions() {
		$exception1 = new Exception( __METHOD__ . ' exception 1' );
		$store1 = $this->createMock( ItemTermStore::class );
		$store1->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->itemId, $this->fingerprint )
			->willThrowException( $exception1 );
		$exception2 = new Exception( __METHOD__ . ' exception 2' );
		$store2 = $this->createMock( ItemTermStore::class );
		$store2->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->itemId, $this->fingerprint )
			->willThrowException( $exception2 );

		$this->setExpectedException( 'Exception', $exception1->getMessage() );
		( new MultiItemTermStore( [ $store1, $store2 ] ) )
			->storeTerms( $this->itemId, $this->fingerprint );
	}

	public function testDeleteTerms_success() {
		$itemTermStores = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$itemTermStore = $this->createMock( ItemTermStore::class );
			$itemTermStore->expects( $this->once() )
				->method( 'deleteTerms' )
				->with( $this->itemId );
			$itemTermStores[] = $itemTermStore;
		}

		( new MultiItemTermStore( $itemTermStores ) )
			->deleteTerms( $this->itemId );
	}

	public function testDeleteTerms_oneException() {
		$exception = new Exception( __METHOD__ . ' exception' );
		$store1 = $this->createMock( ItemTermStore::class );
		$store1->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->itemId )
			->willThrowException( $exception );
		$store2 = $this->createMock( ItemTermStore::class );
		$store2->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->itemId );

		$this->setExpectedException( 'Exception', $exception->getMessage() );
		( new MultiItemTermStore( [ $store1, $store2 ] ) )
			->deleteTerms( $this->itemId );
	}

	public function testDeleteTerms_allExceptions() {
		$exception1 = new Exception( __METHOD__ . ' exception 1' );
		$store1 = $this->createMock( ItemTermStore::class );
		$store1->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->itemId )
			->willThrowException( $exception1 );
		$exception2 = new Exception( __METHOD__ . ' exception 2' );
		$store2 = $this->createMock( ItemTermStore::class );
		$store2->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->itemId )
			->willThrowException( $exception2 );

		$this->setExpectedException( 'Exception', $exception1->getMessage() );
		( new MultiItemTermStore( [ $store1, $store2 ] ) )
			->deleteTerms( $this->itemId );
	}

	public function testGetTerms_success() {
		$store1 = $this->createMock( ItemTermStore::class );
		$store1->expects( $this->once() )
			->method( 'getTerms' )
			->with( $this->itemId )
			->willReturn( $this->fingerprint );
		$store2 = $this->createMock( ItemTermStore::class );
		$store2->expects( $this->never() )
			->method( 'getTerms' );

		$fingerprint = ( new MultiItemTermStore( [ $store1, $store2 ] ) )
			->getTerms( $this->itemId );

		$this->assertSame( $this->fingerprint, $fingerprint );
	}

	public function testGetTerms_fallback() {
		$store1 = $this->createMock( ItemTermStore::class );
		$store1->expects( $this->once() )
			->method( 'getTerms' )
			->with( $this->itemId )
			->willReturn( new Fingerprint( /* empty */ ) );
		$store2 = $this->createMock( ItemTermStore::class );
		$store2->expects( $this->once() )
			->method( 'getTerms' )
			->with( $this->itemId )
			->willReturn( $this->fingerprint );

		$fingerprint = ( new MultiItemTermStore( [ $store1, $store2 ] ) )
			->getTerms( $this->itemId );

		$this->assertSame( $this->fingerprint, $fingerprint );
	}

}
