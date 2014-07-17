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
 * @author Thiemo Mättig
 */
class ChangeOps implements ChangeOp {

	/**
	 * @since 0.4
	 *
	 * @var ChangeOp[]
	 */
	private $changeOps = array();

	/**
	 * @since 0.4
	 *
	 * @param ChangeOp|ChangeOp[] $changeOps
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $changeOps = array() ) {
		$this->add( $changeOps );
	}

	/**
	 * Adds one change operation or a list of change operations.
	 *
	 * @since 0.4
	 *
	 * @param ChangeOp|ChangeOp[] $changeOps
	 *
	 * @throws InvalidArgumentException
	 */
	public function add( $changeOps ) {
		if ( !is_array( $changeOps ) ) {
			$changeOps = array( $changeOps );
		}

		foreach ( $changeOps as $changeOp ) {
			if ( !( $changeOp instanceof ChangeOp ) ) {
				throw new InvalidArgumentException(
					'$changeOp needs to be an instance of ChangeOp or an array of ChangeOps'
				);
			}

			$this->changeOps[] = $changeOp;
		}
	}

	/**
	 * Get the array of change operations.
	 *
	 * @since 0.4
	 *
	 * @return ChangeOp[]
	 */
	public function getChangeOps() {
		return $this->changeOps;
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
	 * @return bool Deprecated, do not rely on this value.
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		foreach ( $this->changeOps as $changeOp ) {
			$changeOp->apply( $entity, $summary );
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
		$entity = $entity->copy();

		foreach ( $this->changeOps as $changeOp ) {
			$result = $changeOp->validate( $entity );

			if ( !$result->isValid() ) {
				// XXX: alternatively, we could collect all the errors.
				break;
			}

			$changeOp->apply( $entity );
		}

		return $result;
	}

}
