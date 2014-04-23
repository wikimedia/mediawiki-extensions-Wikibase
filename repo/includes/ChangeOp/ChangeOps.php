<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
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
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		foreach ( $this->ops as $op ) {
			$op->apply( $entity, $summary );
		}

		return true;
	}

	/**
	 * @see ChangeOp::validate()
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @throws ChangeOpException
	 *
	 * @return Result
	 */
	public function validate( Entity $entity ) {
		$result = Result::newSuccess();

		foreach ( $this->ops as $op ) {
			$result = $op->validate( $entity );

			if ( !$result->isValid() ) {
				// XXX: alternatively, we could collect all the errors.
				break;
			}
		}

		return $result;
	}
}
