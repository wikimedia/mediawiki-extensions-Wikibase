<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Diff\DiffOp\Diff\Diff;
use SiteLookup;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\DispatchingEntityDiffVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;

/**
 * @covers Wikibase\Repo\Diff\DispatchingEntityDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DispatchingEntityDiffVisualizerTest extends \PHPUnit_Framework_TestCase {

	public function testDispatchesRequestToPerEntityTypeVisualizer() {
		$itemVisualizer = $this->getMock( EntityDiffVisualizer::class );
		$itemVisualizer->method( 'visualizeEntityContentDiff' )
			->willReturn( 'ITEM DIFF' );

		$propertyVisualizer = $this->getMock( EntityDiffVisualizer::class );
		$propertyVisualizer->method( 'visualizeEntityContentDiff' )
			->willReturn( 'PrOPERTY DIFF' );

		$factory = $this->newFactory( [
			'item' => function () use ( $itemVisualizer ) {
				return $itemVisualizer;
			},
			'property' => function () use ( $propertyVisualizer ) {
				return $propertyVisualizer;
			}
		] );

		$dispatchingVisualizer = new DispatchingEntityDiffVisualizer( $factory );

		$itemDiff = new EntityContentDiff( new ItemDiff(), new Diff(), 'item' );

		$this->assertEquals( 'ITEM DIFF', $dispatchingVisualizer->visualizeEntityContentDiff( $itemDiff ) );
	}

	private function newFactory( array $instantiators ) {
		return new EntityDiffVisualizerFactory(
			$instantiators,
			new \RequestContext(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			$this->getMockBuilder( ClaimDifferenceVisualizer::class )->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder( SiteLookup::class )->getMock(),
			$this->getMockBuilder( EntityIdFormatter::class )->getMock()
		);
	}

}
