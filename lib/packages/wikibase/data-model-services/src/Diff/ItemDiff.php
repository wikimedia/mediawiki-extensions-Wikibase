<?php

namespace Wikibase\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;

/**
 * Represents a diff between two Item instances.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDiff extends EntityDiff {

	/**
	 * @param DiffOp[] $operations
	 */
	public function __construct( array $operations = [] ) {
		$this->fixSubstructureDiff( $operations, 'links' );

		parent::__construct( $operations );
	}

	/**
	 * Returns a Diff object with the sitelink differences.
	 *
	 * @return Diff
	 */
	public function getSiteLinkDiff() {
		return isset( $this['links'] ) ? $this['links'] : new Diff( [], true );
	}

	/**
	 * @see EntityDiff::isEmpty
	 *
	 * @return bool
	 */
	public function isEmpty(): bool {
		return parent::isEmpty()
			&& $this->getSiteLinkDiff()->isEmpty();
	}

	/**
	 * @see DiffOp::getType
	 *
	 * @return string
	 */
	public function getType(): string {
		return 'diff/item';
	}

}
