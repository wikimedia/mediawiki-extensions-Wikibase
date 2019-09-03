<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Counts changes to entity that are not language bound
 * (not instance of {@link LanguageBoundChangeOpResult}) in {@link ChangeOpResult} tree.
 *
 * Does not count non-leaf nodes (instances of {@link ChangeOpsResult}).
 */
class NonLanguageBoundChangesCounter {

	/**
	 * @param ChangeOpResult $changeOpResult
	 *
	 * @return int count of non-language-bound changes to an entity
	 */
	public function countChanges( ChangeOpResult $changeOpResult ) {
		$changeOpResultsTraversal = new ChangeOpResultTraversal();
		$traversable = $changeOpResultsTraversal->makeRecursiveTraversable( $changeOpResult );

		$count = 0;
		foreach ( $traversable as $result ) {
			if ( !$result instanceof ChangeOpsResult &&
				 !$result instanceof LanguageBoundChangeOpResult &&
				 $result->isEntityChanged()
			) {
				$count += 1;
			}
		}

		return $count;
	}

}
