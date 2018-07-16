<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\DispatchingEntityInfoBuilder;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;

/**
 * @covers \Wikibase\Lib\Store\DispatchingEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityInfoBuilderTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function provideInvalidConstructorArguments() {
		return [
			'empty builder list' => [ [] ],
			'invalid repository name as a key' => [ [ 'fo:oo' => $this->getMock( EntityInfoBuilder::class ) ] ],
			'not an EntityInfoBuilder provided as a builder' => [ [ '' => new ItemId( 'Q111' ) ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArguments_constructorThrowsException( array $args ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new DispatchingEntityInfoBuilder( $args );
	}

	public function testCollectEntityInfoMergesEntityInfoFromAllBuilders() {
		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$localBuilder->expects( $this->any() )
			->method( 'collectEntityInfo' )
			->will( $this->returnValue( new EntityInfo( [
				'Q11' => [ 'id' => 'Q11', 'type' => 'item' ],
			] ) ) );

		$otherBuilder = $this->getMock( EntityInfoBuilder::class );
		$otherBuilder->expects( $this->any() )
			->method( 'collectEntityInfo' )
			->will( $this->returnValue( new EntityInfo( [
				'other:Q22' => [ 'id' => 'other:Q22', 'type' => 'item' ],
			] ) ) );

		$dispatchingBuilder = new DispatchingEntityInfoBuilder( [
			'' => $localBuilder, 'other' => $otherBuilder
		] );

		$this->assertEquals(
			new EntityInfo( [
				'Q11' => [ 'id' => 'Q11', 'type' => 'item' ],
				'other:Q22' => [ 'id' => 'other:Q22', 'type' => 'item' ],
			] ),
			$dispatchingBuilder->collectEntityInfo( [ new ItemId( 'Q11' ), new ItemId( 'other:Q22' ) ], [] )
		);
	}

}
