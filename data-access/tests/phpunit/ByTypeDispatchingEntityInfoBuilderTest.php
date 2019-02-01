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

	public function testCollectEntityInfoMergesResultsFromAllBuilders() {
		$builder = new ByTypeDispatchingEntityInfoBuilder( [
			'item' => new FakeEntityInfoBuilder( [
				'Q1' => [ 'id' => 'Q1', 'type' => 'item' ]
			] ),
			'property' => new FakeEntityInfoBuilder( [
				'P1' => [ 'id' => 'P1', 'type' => 'property' ]
			] )
		] );

		$this->assertSame(
			new EntityInfo( [
				'Q1' => [ 'id' => 'Q1', 'type' => 'item' ],
				'P1' => [ 'id' => 'P1', 'type' => 'property' ],
			] ),
			$builder->collectEntityInfo(
				[
					new ItemId( 'Q1' ),
					new PropertyId( 'P1' )
				],
				[]
			)
		);
	}

	// TODO: update
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

	// TODO: add test for language codes

}
