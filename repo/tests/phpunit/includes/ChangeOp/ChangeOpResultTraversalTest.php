<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpResultTraversal;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpResultsTraverser
 *
 * @group Wikibase
 * @group ChangeOp
 * @license GPL-2.0-or-later
 */
class ChangeOpResultTraversalTest extends \PHPUnit\Framework\TestCase {

	use ChangeOpResultTraversal;

	public function changeOpResultTreesProvider() {
		// visual view of the constructed tree below (numeric suffixes used):
		//       7
		//      / \
		//     6   4
		//    /|\
		//   2 3 5
		//        \
		//         1
		$changeOpResult1 = new ChangeOpResultStub( null, false );
		$changeOpResult2 = new ChangeOpResultStub( null, false );
		$changeOpResult3 = new ChangeOpResultStub( null, false );
		$changeOpResult4 = new ChangeOpResultStub( null, false );

		$changeOpResult5 = new ChangeOpsResult( null, [ $changeOpResult1 ] );
		$changeOpResult6 = new ChangeOpsResult( null,
			[ $changeOpResult2, $changeOpResult3, $changeOpResult5 ] );
		$changeOpResult7 = new ChangeOpsResult( null,
			[ $changeOpResult6, $changeOpResult4 ] );

		return [
			'root node is not ChangeOpsResult' => [
				$changeOpResult1,
				[ $changeOpResult1 ],
			],
			'root node is ChangeOpsResult - child is not ChangeOpsResult' => [
				$changeOpResult5,
				[ $changeOpResult5, $changeOpResult1 ],
			],
			'the full tree' => [
				$changeOpResult7,
				[ $changeOpResult7, $changeOpResult6, $changeOpResult2,
				  $changeOpResult3, $changeOpResult5, $changeOpResult1,
				  $changeOpResult4 ],
			],
		];
	}

	/**
	 * @dataProvider changeOpResultTreesProvider
	 */
	public function testMakeRecursiveTraversable_returnsTraversableRecrusivelyTraversingTree(
		ChangeOpResult $root,
		$expectedVisitedNodes
	) {
		$traversable = $this->makeRecursiveTraversable( $root );

		$actualVisitedNodes = [];
		foreach ( $traversable as $visitedNode ) {
			$actualVisitedNodes[] = $visitedNode;
		}

		$this->assertEquals( $expectedVisitedNodes, $actualVisitedNodes );
	}

}
