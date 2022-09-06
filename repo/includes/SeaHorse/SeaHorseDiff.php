<?php

namespace Wikibase\Repo\SeaHorse;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Wikibase\DataModel\Services\Diff\EntityDiff;

class SeaHorseDiff extends EntityDiff {

	/**
	 * @param DiffOp[] $operations
	 */
	public function __construct( array $operations = [] ) {
		parent::__construct( $operations );
	}

	public function getContentDiff() {
		return $this['content'] ?? new Diff( [], true );
	}

	/**
	 * Returns if there are any changes (equivalent to: any differences between the entities).
	 *
	 * @return bool
	 */
	public function isEmpty(): bool {
		// FIXME: Needs to be fixed, otherwise conflict resolution may lead to unexpected results
		return $this->getContentDiff()->isEmpty();
	}

	public function toArray( callable $valueConverter = null ): array {
		throw new \LogicException( 'toArray() is not implemented' );
	}

}
