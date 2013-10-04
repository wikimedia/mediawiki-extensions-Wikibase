<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\Entity;
use Wikibase\Summary;

/**
 * Class for holding a batch of change operations
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOps {

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
		try {
			foreach ( $this->ops as $op ) {
				$op->apply( $entity, $summary );
			}
		} catch ( ChangeOpException $e ) {
			throw new ChangeOpException( 'Exception while applying changes: ' . $e->getMessage() );
		}

		return true;
	}

}
