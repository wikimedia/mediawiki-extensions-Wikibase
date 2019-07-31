<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\ByIdDispatchingItemTermStore;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\TermStore\ItemTermStore;

/**
 * @covers \Wikibase\ByIdDispatchingItemTermStore
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByIdDispatchingItemTermStoreTest extends TestCase {

	use PHPUnit4And6Compat;

	/** @dataProvider provideMethods */
	public function testMethod( $methodName, array $extraArguments = [], $returnValue = null ) {
		$store1 = $this->createMock( ItemTermStore::class );
		$invocation1 = $store1->expects( $this->once() )
			->method( $methodName )
			->with( ...array_merge( [ new ItemId( 'Q123' ) ], $extraArguments ) );
		if ( $returnValue !== null ) {
			$invocation1->willReturn( $returnValue );
		}

		$store2 = $this->createMock( ItemTermStore::class );
		$invocation2 = $store2->expects( $this->once() )
			->method( $methodName )
			->with( ...array_merge( [ new ItemId( 'Q12345' ) ], $extraArguments ) );
		if ( $returnValue !== null ) {
			$invocation2->willReturn( $returnValue );
		}

		$store3 = $this->createMock( ItemTermStore::class );
		$invocation3 = $store3->expects( $this->once() )
			->method( $methodName )
			->with( ...array_merge( [ new ItemId( 'Q200000' ) ], $extraArguments ) );
		if ( $returnValue !== null ) {
			$invocation3->willReturn( $returnValue );
		}

		$store4 = $this->createMock( ItemTermStore::class );
		$invocation4 = $store4->expects( $this->once() )
			->method( $methodName )
			->with( ...array_merge( [ new ItemId( 'Q1234567' ) ], $extraArguments ) );
		if ( $returnValue !== null ) {
			$invocation4->willReturn( $returnValue );
		}

		$store = new ByIdDispatchingItemTermStore( [
			1000 => $store1,
			200000 => $store3,
			100000 => $store2,
			Int32EntityId::MAX => $store4,
		] );

		$returnValue1 = $store->$methodName( ...array_merge( [ new ItemId( 'Q123' ) ], $extraArguments ) );
		if ( $returnValue !== null ) {
			$this->assertSame( $returnValue, $returnValue1 );
		}

		$returnValue2 = $store->$methodName( ...array_merge( [ new ItemId( 'Q12345' ) ], $extraArguments ) );
		if ( $returnValue !== null ) {
			$this->assertSame( $returnValue, $returnValue2 );
		}

		$returnValue3 = $store->$methodName( ...array_merge( [ new ItemId( 'Q200000' ) ], $extraArguments ) );
		if ( $returnValue !== null ) {
			$this->assertSame( $returnValue, $returnValue3 );
		}

		$returnValue4 = $store->$methodName( ...array_merge( [ new ItemId( 'Q1234567' ) ], $extraArguments ) );
		if ( $returnValue !== null ) {
			$this->assertSame( $returnValue, $returnValue4 );
		}
	}

	public function provideMethods() {
		yield 'storeTerms' => [ 'storeTerms', [ new Fingerprint() ], null ];
		yield 'deleteTerms' => [ 'deleteTerms' ];
		yield 'getTerms' => [ 'getTerms', [], new Fingerprint() ];
	}

}
