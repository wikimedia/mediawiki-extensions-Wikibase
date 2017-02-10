<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\DispatchingEntityInfoBuilder;
use Wikibase\Lib\Store\DispatchingEntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;

/**
 * @covers Wikibase\Lib\Store\DispatchingEntityInfoBuilderFactory
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 */
class DispatchingEntityInfoBuilderFactoryTest extends \PHPUnit_Framework_TestCase {

	public function provideInvalidFactoryLists() {
		return [
			'empty factory list' => [ [] ],
			'invalid repository name as a key' => [ [ 'fo:oo' => $this->getMock( EntityInfoBuilderFactory::class ) ] ],
			'not an EntityInfoBuilderFactory provided as a factory' => [ [ '' => new ItemId( 'Q111' ) ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidFactoryLists
	 */
	public function testGivenInvalidFactoryList_constructorThrowsException( $factories ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new DispatchingEntityInfoBuilderFactory( $factories );
	}

	public function testNewEntityInfoBuilderCreatesDispatchingBuilderUsingKnownBuilders() {
		$localBuilder = $this->getMock( EntityInfoBuilder::class );
		$otherBuilder = $this->getMock( EntityInfoBuilder::class );

		$localFactory = $this->getMock( EntityInfoBuilderFactory::class );
		$localFactory->expects( $this->atLeastOnce() )
			->method( 'newEntityInfoBuilder' )
			->will( $this->returnValue( $localBuilder ) );

		$otherFactory = $this->getMock( EntityInfoBuilderFactory::class );
		$otherFactory->expects( $this->atLeastOnce() )
			->method( 'newEntityInfoBuilder' )
			->will( $this->returnValue( $otherBuilder ) );

		$dispatchingFactory = new DispatchingEntityInfoBuilderFactory(
			[
				'' => $localFactory,
				'other' => $otherFactory
			]
		);

		$this->assertEquals(
			new DispatchingEntityInfoBuilder( [
				'' => $localBuilder,
				'other' => $otherBuilder,
			] ),
			$dispatchingFactory->newEntityInfoBuilder( [ new ItemId( 'Q10' ), new PropertyId( 'other:P20' ) ] )
		);
	}

	public function testNewEntityInfoBuilderAlwaysReturnsNewInstance() {
		$itemIdOne = new ItemId( 'Q10' );
		$itemIdTwo = new ItemId( 'Q11' );

		$localFactory = $this->getMock( EntityInfoBuilderFactory::class );
		$localFactory->method( 'newEntityInfoBuilder' )
			->will( $this->returnValue( $this->getMock( EntityInfoBuilder::class ) ) );

		$dispatchingFactory = new DispatchingEntityInfoBuilderFactory(
			[ '' => $localFactory ]
		);

		$builderOne = $dispatchingFactory->newEntityInfoBuilder( [ $itemIdOne, $itemIdTwo ] );
		$builderTwo = $dispatchingFactory->newEntityInfoBuilder( [ $itemIdOne, $itemIdTwo ] );

		$this->assertNotSame( $builderOne, $builderTwo );
	}

}
