<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use RequestContext;
use SiteLookup;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Diff\EntityDiffVisualizerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class EntityDiffVisualizerFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testGivenNoType_factoryReturnsBasicEntityDiffVisualizer() {
		$factory = $this->newFactory();

		$this->assertInstanceOf(
			BasicEntityDiffVisualizer::class,
			$factory->newEntityDiffVisualizer( null, new RequestContext() )
		);
	}

	public function testGivenInstantiatorForEntityType_factoryReturnsBasicEntityDiffVisualizer() {
		$factory = $this->newFactory();

		$this->assertInstanceOf(
			BasicEntityDiffVisualizer::class,
			$factory->newEntityDiffVisualizer( 'item', new RequestContext() )
		);
	}

	public function testGivenInstantiatorForEntityType_factoryUsesIt() {
		$dummyVisualizer = $this->createMock( EntityDiffVisualizer::class );

		$factory = $this->newFactory( [
			'item' => function () use ( $dummyVisualizer ) {
				return $dummyVisualizer;
			},
		] );

		$this->assertSame( $dummyVisualizer, $factory->newEntityDiffVisualizer( 'item', new RequestContext() ) );
	}

	public function testGivenInstantiatorDoesReturnDiffVisualizer_factoryThrowsException() {
		$factory = $this->newFactory( [
			'item' => function () {
				return 'WOOO';
			},
		] );

		$this->expectException( \LogicException::class );

		$factory->newEntityDiffVisualizer( 'item', new RequestContext() );
	}

	/**
	 * @dataProvider provideInvalidConstructorArgs
	 */
	public function testGivenInvalidInstantiators_constructorThrowsException( $instantiators ) {
		$this->expectException( \InvalidArgumentException::class );

		$this->newFactory( $instantiators );
	}

	public function provideInvalidConstructorArgs() {
		return [
			[ 'non-string key' => [
				123 => function () {
					return $this->createMock( EntityDiffVisualizer::class );
				},
			] ],
			[ 'not a callable' => [ 'item' => 'WOOO' ] ],
		];
	}

	private function newFactory( array $instantiators = [] ) {
		return new EntityDiffVisualizerFactory(
			$instantiators,
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			$this->createMock( SiteLookup::class ),
			WikibaseRepo::getEntityIdHTMLLinkFormatterFactory(),
			WikibaseRepo::getSnakFormatterFactory()
		);
	}

}
