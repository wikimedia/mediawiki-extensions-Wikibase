<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\ByIdDispatchingEntityInfoBuilder;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;

/**
 * @covers \Wikibase\ByIdDispatchingEntityInfoBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByIdDispatchingEntityInfoBuilderTest extends \PHPUnit\Framework\TestCase {

	public function testMethod() {
		$entityInfo = new EntityInfo( [] );

		$store1 = $this->createMock( EntityInfoBuilder::class );
		$store1->expects( $this->once() )
			->method( 'collectEntityInfo' )
			->with( [ new ItemId( 'Q123' ) ], [ 'en' ] )
			->willReturn( new EntityInfo( [ 'Q123' ] ) );

		$store2 = $this->createMock( EntityInfoBuilder::class );
		$store2->expects( $this->once() )
			->method( 'collectEntityInfo' )
			->with( [ new ItemId( 'Q12345' ) ], [ 'en' ] )
			->willReturn( new EntityInfo( [ 'Q12345' ] ) );

		$store3 = $this->createMock( EntityInfoBuilder::class );
		$store3->expects( $this->once() )
			->method( 'collectEntityInfo' )
			->with( [ new ItemId( 'Q200000' ) ], [ 'en' ] )
			->willReturn( new EntityInfo( [ 'Q200000' ] ) );

		$store4 = $this->createMock( EntityInfoBuilder::class );
		$store4->expects( $this->once() )
			->method( 'collectEntityInfo' )
			->with( [ new ItemId( 'Q1234567' ) ], [ 'en' ] )
			->willReturn( new EntityInfo( [ 'Q1234567' ] ) );

		$builder = new ByIdDispatchingEntityInfoBuilder( [
			1000 => $store1,
			210000 => $store3,
			100000 => $store2,
			Int32EntityId::MAX => $store4,
		] );

		$returnValue1 = $builder->collectEntityInfo( [ new ItemId( 'Q123' ) ], [ 'en' ] );
		$this->assertEquals( new EntityInfo( [ 'Q123' ] ), $returnValue1 );

		$returnValue2 = $builder->collectEntityInfo( [ new ItemId( 'Q12345' ) ], [ 'en' ] );
		$this->assertEquals( new EntityInfo( [ 'Q12345' ] ), $returnValue2 );

		$returnValue3 = $builder->collectEntityInfo( [ new ItemId( 'Q200000' ) ], [ 'en' ] );
		$this->assertEquals( new EntityInfo( [ 'Q200000' ] ), $returnValue3 );

		$returnValue4 = $builder->collectEntityInfo( [ new ItemId( 'Q1234567' ) ], [ 'en' ] );
		$this->assertEquals( new EntityInfo( [ 'Q1234567' ] ), $returnValue4 );
	}

}
