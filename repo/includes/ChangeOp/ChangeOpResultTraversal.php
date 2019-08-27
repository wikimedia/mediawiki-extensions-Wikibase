<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Provides traversal interfaces of ChangeOpResult tree
 */
class ChangeOpResultTraversal {

	/**
	 * creates a new recursive traversable on ChangeOpResult tree
	 *
	 * ChangeOpResults will be yielded, including inner nodes of ChangeOpsResult.
	 * Order of traversal is not defined.
	 *
	 * @return \Traversable
	 */
	public function makeRecursiveTraversable( ChangeOpResult $changeOpResult ) {
		// TODO (PHP7): use yield from instead when we move to php7
		foreach ( $this->yieldFrom( $changeOpResult ) as $result ) {
			yield $result;
		}
	}

	private function yieldFrom( ChangeOpResult $changeOpResult ) {
		yield $changeOpResult;

		if ( $changeOpResult instanceof ChangeOpsResult ) {
			foreach ( $changeOpResult->getChangeOpsResults() as $childResult ) {
				// TODO (PHP7): use yield from instead when we move to php7
				foreach ( $this->yieldFrom( $childResult ) as $result ) {
					yield $result;
				}
			}
		}
	}

}
