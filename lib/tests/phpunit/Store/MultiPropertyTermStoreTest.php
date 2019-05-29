<?php

namespace Wikibase\Lib\Tests\Store;

use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\MultiPropertyTermStore;
use Wikibase\TermStore\PropertyTermStore;

/**
 * @covers \Wikibase\MultiPropertyTermStore
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultiPropertyTermStoreTest extends TestCase {

	use PHPUnit4And6Compat;

	/** @var PropertyId */
	private $propertyId;

	/** @var Fingerprint */
	private $fingerprint;

	protected function setUp() {
		parent::setUp();
		$this->propertyId = new PropertyId( 'P1' );
		$this->fingerprint = new Fingerprint(
			new TermList( [ new Term( 'en', 'a label' ) ] ),
			new TermList( [ new Term( 'en', 'a description' ) ] ),
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'an alias', 'another alias' ] )
			] )
		);
	}

	public function testStoreTerms_success() {
		$propertyTermStores = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$propertyTermStore = $this->createMock( PropertyTermStore::class );
			$propertyTermStore->expects( $this->once() )
				->method( 'storeTerms' )
				->with( $this->propertyId, $this->fingerprint );
			$propertyTermStores[] = $propertyTermStore;
		}

		( new MultiPropertyTermStore( $propertyTermStores ) )
			->storeTerms( $this->propertyId, $this->fingerprint );
	}

	public function testStoreTerms_oneException() {
		$exception = new Exception( __METHOD__ . ' exception' );
		$store1 = $this->createMock( PropertyTermStore::class );
		$store1->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->propertyId, $this->fingerprint )
			->willThrowException( $exception );
		$store2 = $this->createMock( PropertyTermStore::class );
		$store2->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->propertyId, $this->fingerprint );

		$this->expectExceptionMessage( $exception->getMessage() );
		( new MultiPropertyTermStore( [ $store1, $store2 ] ) )
			->storeTerms( $this->propertyId, $this->fingerprint );
	}

	public function testStoreTerms_allExceptions() {
		$exception1 = new Exception( __METHOD__ . ' exception 1' );
		$store1 = $this->createMock( PropertyTermStore::class );
		$store1->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->propertyId, $this->fingerprint )
			->willThrowException( $exception1 );
		$exception2 = new Exception( __METHOD__ . ' exception 2' );
		$store2 = $this->createMock( PropertyTermStore::class );
		$store2->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->propertyId, $this->fingerprint )
			->willThrowException( $exception2 );

		$this->expectExceptionMessage( $exception1->getMessage() );
		( new MultiPropertyTermStore( [ $store1, $store2 ] ) )
			->storeTerms( $this->propertyId, $this->fingerprint );
	}

	public function testDeleteTerms_success() {
		$propertyTermStores = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$propertyTermStore = $this->createMock( PropertyTermStore::class );
			$propertyTermStore->expects( $this->once() )
				->method( 'deleteTerms' )
				->with( $this->propertyId );
			$propertyTermStores[] = $propertyTermStore;
		}

		( new MultiPropertyTermStore( $propertyTermStores ) )
			->deleteTerms( $this->propertyId );
	}

	public function testDeleteTerms_oneException() {
		$exception = new Exception( __METHOD__ . ' exception' );
		$store1 = $this->createMock( PropertyTermStore::class );
		$store1->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->propertyId )
			->willThrowException( $exception );
		$store2 = $this->createMock( PropertyTermStore::class );
		$store2->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->propertyId );

		$this->expectExceptionMessage( $exception->getMessage() );
		( new MultiPropertyTermStore( [ $store1, $store2 ] ) )
			->deleteTerms( $this->propertyId );
	}

	public function testDeleteTerms_allExceptions() {
		$exception1 = new Exception( __METHOD__ . ' exception 1' );
		$store1 = $this->createMock( PropertyTermStore::class );
		$store1->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->propertyId )
			->willThrowException( $exception1 );
		$exception2 = new Exception( __METHOD__ . ' exception 2' );
		$store2 = $this->createMock( PropertyTermStore::class );
		$store2->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->propertyId )
			->willThrowException( $exception2 );

		$this->expectExceptionMessage( $exception1->getMessage() );
		( new MultiPropertyTermStore( [ $store1, $store2 ] ) )
			->deleteTerms( $this->propertyId );
	}

	public function testGetTerms() {
		$store1 = $this->createMock( PropertyTermStore::class );
		$store1->expects( $this->once() )
			->method( 'getTerms' )
			->with( $this->propertyId )
			->willReturn( $this->fingerprint );
		$store2 = $this->createMock( PropertyTermStore::class );
		$store2->expects( $this->never() )
			->method( 'getTerms' );

		$fingerprint = ( new MultiPropertyTermStore( [ $store1, $store2 ] ) )
			->getTerms( $this->propertyId );

		$this->assertSame( $this->fingerprint, $fingerprint );
	}

}
