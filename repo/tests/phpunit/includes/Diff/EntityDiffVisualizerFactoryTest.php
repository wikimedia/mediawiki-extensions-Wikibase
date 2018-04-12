<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use PHPUnit4And6Compat;
use RequestContext;
use SiteLookup;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;

/**
 * @covers Wikibase\Repo\Diff\EntityDiffVisualizerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class EntityDiffVisualizerFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testGivenNoType_factoryReturnsBasicEntityDiffVisualizer() {
		$factory = $this->newFactory();

		$this->assertInstanceOf(
			BasicEntityDiffVisualizer::class,
			$factory->newEntityDiffVisualizer()
		);
	}

	public function testGivenInstantiatorForEntityType_factoryReturnsBasicEntityDiffVisualizer() {
		$factory = $this->newFactory();

		$this->assertInstanceOf(
			BasicEntityDiffVisualizer::class,
			$factory->newEntityDiffVisualizer( 'item' )
		);
	}

	public function testGivenInstantiatorForEntityType_factoryUsesIt() {
		$dummyVisualizer = $this->getMock( EntityDiffVisualizer::class );

		$factory = $this->newFactory( [
			'item' => function () use ( $dummyVisualizer ) {
				return $dummyVisualizer;
			},
		] );

		$this->assertSame( $dummyVisualizer, $factory->newEntityDiffVisualizer( 'item' ) );
	}

	public function testGivenInstantiatorDoesReturnDiffVisualizer_factoryThrowsException() {
		$factory = $this->newFactory( [
			'item' => function () {
				return 'WOOO';
			}
		] );

		$this->setExpectedException( \LogicException::class );

		$factory->newEntityDiffVisualizer( 'item' );
	}

	/**
	 * @dataProvider provideInvalidConstructorArgs
	 */
	public function testGivenInvalidInstantiators_constructorThrowsException( $instantiators ) {
		$this->setExpectedException( \InvalidArgumentException::class );

		$this->newFactory( $instantiators );
	}

	public function provideInvalidConstructorArgs() {
		return [
			[ 'non-string key' => [
				123 => function () {
					return $this->getMock( EntityDiffVisualizer::class );
				}
			] ],
			[ 'not a callable' => [ 'item' => 'WOOO' ] ],
		];
	}

	private function newFactory( array $instantiators = [] ) {
		return new EntityDiffVisualizerFactory(
			$instantiators,
			new RequestContext(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			$this->getMockBuilder( ClaimDifferenceVisualizer::class )->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder( SiteLookup::class )->getMock(),
			$this->getMockBuilder( EntityIdFormatter::class )->getMock()
		);
	}

}
