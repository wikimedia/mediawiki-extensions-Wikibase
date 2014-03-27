<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;

/**
 * Class for holding a batch of change operations
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOps implements ChangeOp {

	/**
	 * @since 0.4
	 *
	 * @var ChangeOp[]
	 */
	protected $ops;

	/**
	 * @since 0.4
	 *
	 */
	public function __construct() {
		$this->ops = array();
	}

	/**
	 * Adds a changeOp
	 *
	 * @since 0.4
	 *
	 * @param ChangeOp|ChangeOp[] $changeOp
	 *
	 * @throws InvalidArgumentException
	 */
	public function add( $changeOp ) {
		if ( !is_array( $changeOp ) && !( $changeOp instanceof ChangeOp ) ) {
			throw new InvalidArgumentException( '$changeOp needs to be an instance of ChangeOp or an array of ChangeOps' );
		}

		if ( $changeOp instanceof ChangeOp ) {
			$this->ops[] = $changeOp;
		} else {
			foreach ( $changeOp as $op ) {
				if ( $op instanceof ChangeOp ) {
					$this->ops[] = $op;
				} else {
					throw new InvalidArgumentException( 'array $changeOp must contain ChangeOps only' );
				}
			}
		}
	}

	/**
	 * Get the array of changeOps
	 *
	 * @since 0.4
	 *
	 * @return ChangeOp[]
	 */
	public function getChangeOps() {
		return $this->ops;
	}

	/**
	 * Applies all changes to the given entity
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 * @return bool
	 *
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		foreach ( $this->ops as $op ) {
			$op->apply( $entity, $summary );
		}

		return true;
	}

	/**
	 * Returns a ChangeOps instance representing the same changes as this ChangeOps,
	 * possibly combined into batches for more efficient validation and application.
	 *
	 * @note: The global order of ChangeOps may be different in the batched ChangeOps.
	 *        However, ChangeOps of the same type will remain in the same order
	 *        relative to each other.
	 * @note: Currently, this just returns $this
	 * @todo: implement batching at least for labels and descriptions, perhaps also sitelinks.
	 * @todo: test me
	 *
	 * @since 0.5
	 *
	 * @return $this
	 */
	public function getBatchedOps() {
		return $this;
	}
}
