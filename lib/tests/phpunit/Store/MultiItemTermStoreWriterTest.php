<?php

namespace Wikibase\Lib\Tests\Store;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\MultiItemTermStoreWriter;

/**
 * @covers \Wikibase\Lib\Store\MultiItemTermStoreWriter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultiItemTermStoreWriterTest extends TestCase {

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

	public function testStoreTerms_success() {
		$itemTermStores = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$itemTermStoreWriter = $this->createMock( ItemTermStoreWriter::class );
			$itemTermStoreWriter->expects( $this->once() )
				->method( 'storeTerms' )
				->with( $this->itemId, $this->fingerprint );
			$itemTermStoreWriters[] = $itemTermStoreWriter;
		}

		( new MultiItemTermStoreWriter( $itemTermStoreWriters ) )
			->storeTerms( $this->itemId, $this->fingerprint );
	}

	public function testStoreTerms_oneException() {
		$exception = new Exception( __METHOD__ . ' exception' );
		$store1 = $this->createMock( ItemTermStoreWriter::class );
		$store1->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->itemId, $this->fingerprint )
			->willThrowException( $exception );
		$store2 = $this->createMock( ItemTermStoreWriter::class );
		$store2->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->itemId, $this->fingerprint );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $exception->getMessage() );
		( new MultiItemTermStoreWriter( [ $store1, $store2 ] ) )
			->storeTerms( $this->itemId, $this->fingerprint );
	}

	public function testStoreTerms_allExceptions() {
		$exception1 = new Exception( __METHOD__ . ' exception 1' );
		$store1 = $this->createMock( ItemTermStoreWriter::class );
		$store1->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->itemId, $this->fingerprint )
			->willThrowException( $exception1 );
		$exception2 = new Exception( __METHOD__ . ' exception 2' );
		$store2 = $this->createMock( ItemTermStoreWriter::class );
		$store2->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->itemId, $this->fingerprint )
			->willThrowException( $exception2 );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $exception1->getMessage() );
		( new MultiItemTermStoreWriter( [ $store1, $store2 ] ) )
			->storeTerms( $this->itemId, $this->fingerprint );
	}

	public function testDeleteTerms_success() {
		$itemTermStoreWriters = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$itemTermStoreWriter = $this->createMock( ItemTermStoreWriter::class );
			$itemTermStoreWriter->expects( $this->once() )
				->method( 'deleteTerms' )
				->with( $this->itemId );
			$itemTermStoreWriters[] = $itemTermStoreWriter;
		}

		( new MultiItemTermStoreWriter( $itemTermStoreWriters ) )
			->deleteTerms( $this->itemId );
	}

	public function testDeleteTerms_oneException() {
		$exception = new Exception( __METHOD__ . ' exception' );
		$store1 = $this->createMock( ItemTermStoreWriter::class );
		$store1->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->itemId )
			->willThrowException( $exception );
		$store2 = $this->createMock( ItemTermStoreWriter::class );
		$store2->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->itemId );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $exception->getMessage() );
		( new MultiItemTermStoreWriter( [ $store1, $store2 ] ) )
			->deleteTerms( $this->itemId );
	}

	public function testDeleteTerms_allExceptions() {
		$exception1 = new Exception( __METHOD__ . ' exception 1' );
		$store1 = $this->createMock( ItemTermStoreWriter::class );
		$store1->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->itemId )
			->willThrowException( $exception1 );
		$exception2 = new Exception( __METHOD__ . ' exception 2' );
		$store2 = $this->createMock( ItemTermStoreWriter::class );
		$store2->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->itemId )
			->willThrowException( $exception2 );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $exception1->getMessage() );
		( new MultiItemTermStoreWriter( [ $store1, $store2 ] ) )
			->deleteTerms( $this->itemId );
	}

}
