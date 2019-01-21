<?php

namespace Wikibase\DataAccess\Tests;

use PHPUnit4And6Compat;
use Wikibase\DataAccess\ByTypeDispatchingEntityInfoBuilder;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;

/**
 * @covers \Wikibase\DataAccess\ByTypeDispatchingEntityInfoBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityInfoBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenNotEntityInfoBuilderInstance_constructorThrowsException() {
		new ByTypeDispatchingEntityInfoBuilder( [ 'item' => 'FOOBAR' ] );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenNotStringIndexedArray_constructorThrowsException() {
		new ByTypeDispatchingEntityInfoBuilder( [ $this->createMock( EntityInfoBuilder::class ) ] );
	}

	public function testCollectEntityInfoPassesRequestToBuildersDefinedForRelevantEntityTypes() {
		$itemId = new ItemId( 'Q1' );
		$propertyId = new PropertyId( 'P1' );

		$itemInfoBuilder = $this->createMock( EntityInfoBuilder::class );
		$itemInfoBuilder->expects( $this->atLeastOnce() )
			->method( 'collectEntityInfo' )
			->with( [ $itemId ] )
			->willReturn( new EntityInfo( [] ) );

		$propertyInfoBuilder = $this->createMock( EntityInfoBuilder::class );
		$propertyInfoBuilder->expects( $this->atLeastOnce() )
			->method( 'collectEntityInfo' )
			->with( [ $propertyId ] )
			->willReturn( new EntityInfo( [] ) );

		$builder = new ByTypeDispatchingEntityInfoBuilder( [ 'item' => $itemInfoBuilder, 'property' => $propertyInfoBuilder ] );

		$builder->collectEntityInfo( [ $itemId, $propertyId ], [ 'en' ] );
	}

	public function testCollectEntityInfoMergesResultsFromAllBuilders() {
		$itemId = new ItemId( 'Q1' );
		$propertyId = new PropertyId( 'P1' );

		$itemInfoBuilder = $this->createMock( EntityInfoBuilder::class );
		$itemInfoBuilder->method( 'collectEntityInfo' )
			->willReturn( new EntityInfo( [ 'Q1' => [ 'id' => 'Q1', 'type' => 'item' ] ] ) );

		$propertyInfoBuilder = $this->createMock( EntityInfoBuilder::class );
		$propertyInfoBuilder->method( 'collectEntityInfo' )
			->willReturn( new EntityInfo( [ 'P1' => [ 'id' => 'P1', 'type' => 'property' ] ] ) );

		$builder = new ByTypeDispatchingEntityInfoBuilder( [ 'item' => $itemInfoBuilder, 'property' => $propertyInfoBuilder ] );

		$this->assertEquals(
			new EntityInfo( [
				'Q1' => [ 'id' => 'Q1', 'type' => 'item' ],
				'P1' => [ 'id' => 'P1', 'type' => 'property' ],
			] ),
			$builder->collectEntityInfo( [ $itemId, $propertyId ], [ 'en' ] )
		);
	}

	public function testCollectEntityInfoOmitsEntitiesOfUnknownType() {
		$itemId = new ItemId( 'Q1' );
		$propertyId = new PropertyId( 'P1' );

		$itemInfoBuilder = $this->createMock( EntityInfoBuilder::class );
		$itemInfoBuilder->method( 'collectEntityInfo' )
			->willReturn( new EntityInfo( [ 'Q1' => [ 'id' => 'Q1', 'type' => 'item' ] ] ) );

		$builder = new ByTypeDispatchingEntityInfoBuilder( [ 'item' => $itemInfoBuilder ] );

		$entityInfo = $builder->collectEntityInfo( [ $itemId, $propertyId ], [ 'en' ] );

		$this->assertFalse( $entityInfo->hasEntityInfo( $propertyId ) );
	}

}
