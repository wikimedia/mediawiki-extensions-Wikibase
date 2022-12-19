<?php

namespace Wikibase\Lib\Tests\Store;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\MultiPropertyTermStoreWriter;

/**
 * @covers \Wikibase\Lib\Store\MultiPropertyTermStoreWriter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultiPropertyTermStoreWriterTest extends TestCase {

	/** @var NumericPropertyId */
	private $propertyId;

	/** @var Fingerprint */
	private $fingerprint;

	protected function setUp(): void {
		parent::setUp();
		$this->propertyId = new NumericPropertyId( 'P1' );
		$this->fingerprint = new Fingerprint(
			new TermList( [ new Term( 'en', 'a label' ) ] ),
			new TermList( [ new Term( 'en', 'a description' ) ] ),
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'an alias', 'another alias' ] ),
			] )
		);
	}

	public function testStoreTerms_success() {
		$propertyTermStoreWriters = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$propertyTermStoreWriter = $this->createMock( PropertyTermStoreWriter::class );
			$propertyTermStoreWriter->expects( $this->once() )
				->method( 'storeTerms' )
				->with( $this->propertyId, $this->fingerprint );
			$propertyTermStoreWriters[] = $propertyTermStoreWriter;
		}

		( new MultiPropertyTermStoreWriter( $propertyTermStoreWriters ) )
			->storeTerms( $this->propertyId, $this->fingerprint );
	}

	public function testStoreTerms_oneException() {
		$exception = new Exception( __METHOD__ . ' exception' );
		$store1 = $this->createMock( PropertyTermStoreWriter::class );
		$store1->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->propertyId, $this->fingerprint )
			->willThrowException( $exception );
		$store2 = $this->createMock( PropertyTermStoreWriter::class );
		$store2->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->propertyId, $this->fingerprint );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $exception->getMessage() );
		( new MultiPropertyTermStoreWriter( [ $store1, $store2 ] ) )
			->storeTerms( $this->propertyId, $this->fingerprint );
	}

	public function testStoreTerms_allExceptions() {
		$exception1 = new Exception( __METHOD__ . ' exception 1' );
		$store1 = $this->createMock( PropertyTermStoreWriter::class );
		$store1->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->propertyId, $this->fingerprint )
			->willThrowException( $exception1 );
		$exception2 = new Exception( __METHOD__ . ' exception 2' );
		$store2 = $this->createMock( PropertyTermStoreWriter::class );
		$store2->expects( $this->once() )
			->method( 'storeTerms' )
			->with( $this->propertyId, $this->fingerprint )
			->willThrowException( $exception2 );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $exception1->getMessage() );
		( new MultiPropertyTermStoreWriter( [ $store1, $store2 ] ) )
			->storeTerms( $this->propertyId, $this->fingerprint );
	}

	public function testDeleteTerms_success() {
		$propertyTermStoreWriters = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$propertyTermStoreWriter = $this->createMock( PropertyTermStoreWriter::class );
			$propertyTermStoreWriter->expects( $this->once() )
				->method( 'deleteTerms' )
				->with( $this->propertyId );
			$propertyTermStoreWriters[] = $propertyTermStoreWriter;
		}

		( new MultiPropertyTermStoreWriter( $propertyTermStoreWriters ) )
			->deleteTerms( $this->propertyId );
	}

	public function testDeleteTerms_oneException() {
		$exception = new Exception( __METHOD__ . ' exception' );
		$store1 = $this->createMock( PropertyTermStoreWriter::class );
		$store1->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->propertyId )
			->willThrowException( $exception );
		$store2 = $this->createMock( PropertyTermStoreWriter::class );
		$store2->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->propertyId );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $exception->getMessage() );
		( new MultiPropertyTermStoreWriter( [ $store1, $store2 ] ) )
			->deleteTerms( $this->propertyId );
	}

	public function testDeleteTerms_allExceptions() {
		$exception1 = new Exception( __METHOD__ . ' exception 1' );
		$store1 = $this->createMock( PropertyTermStoreWriter::class );
		$store1->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->propertyId )
			->willThrowException( $exception1 );
		$exception2 = new Exception( __METHOD__ . ' exception 2' );
		$store2 = $this->createMock( PropertyTermStoreWriter::class );
		$store2->expects( $this->once() )
			->method( 'deleteTerms' )
			->with( $this->propertyId )
			->willThrowException( $exception2 );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $exception1->getMessage() );
		( new MultiPropertyTermStoreWriter( [ $store1, $store2 ] ) )
			->deleteTerms( $this->propertyId );
	}

}
