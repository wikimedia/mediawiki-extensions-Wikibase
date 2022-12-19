<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Diff\DiffOp\Diff\Diff;
use RequestContext;
use SiteLookup;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\DispatchingEntityDiffVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Diff\DispatchingEntityDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityDiffVisualizerTest extends \PHPUnit\Framework\TestCase {

	public function testDispatchesRequestToPerEntityTypeVisualizer() {
		$itemVisualizer = $this->createMock( EntityDiffVisualizer::class );
		$itemVisualizer->method( 'visualizeEntityContentDiff' )
			->willReturn( 'ITEM DIFF' );

		$propertyVisualizer = $this->createMock( EntityDiffVisualizer::class );
		$propertyVisualizer->method( 'visualizeEntityContentDiff' )
			->willReturn( 'PrOPERTY DIFF' );

		$factory = $this->newFactory( [
			'item' => function () use ( $itemVisualizer ) {
				return $itemVisualizer;
			},
			'property' => function () use ( $propertyVisualizer ) {
				return $propertyVisualizer;
			},
		] );

		$dispatchingVisualizer = new DispatchingEntityDiffVisualizer( $factory, new RequestContext() );

		$itemDiff = new EntityContentDiff( new ItemDiff(), new Diff(), 'item' );

		$this->assertEquals( 'ITEM DIFF', $dispatchingVisualizer->visualizeEntityContentDiff( $itemDiff ) );
	}

	private function newFactory( array $instantiators ) {
		return new EntityDiffVisualizerFactory(
			$instantiators,
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			$this->createMock( SiteLookup::class ),
			WikibaseRepo::getEntityIdHtmlLinkFormatterFactory(),
			WikibaseRepo::getSnakFormatterFactory()
		);
	}

}
