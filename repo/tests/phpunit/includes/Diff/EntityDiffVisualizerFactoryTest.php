<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use MediaWikiTestCase;
use RequestContext;
use SiteLookup;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;

/**
 * @covers Wikibase\Repo\Diff\EntityDiffVisualizerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class EntityDiffVisualizerFactoryTest extends MediaWikiTestCase {

	public function testBasicEntityDiffVisualizer() {
		$entityDiffVisualizerFactory = new EntityDiffVisualizerFactory(
			[],
			new RequestContext(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			$this->getMockBuilder( ClaimDifferenceVisualizer::class )->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder( SiteLookup::class )->getMock(),
			$this->getMockBuilder( EntityIdFormatter::class )->getMock()
		);

		$this->assertInstanceOf(
			BasicEntityDiffVisualizer::class,
			$entityDiffVisualizerFactory->newEntityDiffVisualizer()
		);

		$this->assertInstanceOf(
			BasicEntityDiffVisualizer::class,
			$entityDiffVisualizerFactory->newEntityDiffVisualizer( 'item' )
		);
	}

}
