<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Provides traversal interfaces of ChangeOpResult tree
 * @license GPL-2.0-or-later
 */
trait ChangeOpResultTraversal {

	/**
	 * creates a new recursive traversable on ChangeOpResult tree
	 *
	 * ChangeOpResults will be yielded, including inner nodes of ChangeOpsResult.
	 * Order of traversal is not defined.
	 *
	 * @return \Traversable
	 */
	public function makeRecursiveTraversable( ChangeOpResult $changeOpResult ) {
		yield from $this->yieldFrom( $changeOpResult );
	}

	private function yieldFrom( ChangeOpResult $changeOpResult ) {
		yield $changeOpResult;

		if ( $changeOpResult instanceof ChangeOpsResult ) {
			foreach ( $changeOpResult->getChangeOpsResults() as $childResult ) {
				yield from $this->yieldFrom( $childResult );
			}
		}
	}

}
