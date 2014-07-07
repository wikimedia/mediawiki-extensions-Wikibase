<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;

/**
 * Represents a diff between two Item instances.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDiff extends EntityDiff {

	/**
	 * @param DiffOp[] $operations
	 */
	public function __construct( array $operations = array() ) {
		$this->fixSubstructureDiff( $operations, 'links' );

		parent::__construct( $operations, true );
	}

	/**
	 * Returns a Diff object with the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return Diff
	 */
	public function getSiteLinkDiff() {
		return isset( $this['links'] ) ? $this['links'] : new Diff( array(), true );
	}

	/**
	 * @see EntityDiff::isEmpty
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return parent::isEmpty()
			&& $this->getSiteLinkDiff()->isEmpty();
	}

	/**
	 * @see DiffOp::getType
	 *
	 * @return string
	 */
	public function getType() {
		return 'diff/item';
	}

}
