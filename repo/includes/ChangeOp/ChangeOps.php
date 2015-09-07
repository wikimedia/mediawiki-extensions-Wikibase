<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Summary;

/**
 * Class for holding a batch of change operations
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Mättig
 */
class ChangeOps implements ChangeOp {

	/**
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
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		foreach ( $this->changeOps as $changeOp ) {
			$changeOp->apply( $entity, $summary );
		}
	}

	/**
	 * @see ChangeOp::validate()
	 *
	 * @since 0.5
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws ChangeOpException
	 * @return Result
	 */
	public function validate( EntityDocument $entity ) {
		$result = Result::newSuccess();
		// deep clone of $entity to avoid side-effects
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

	/**
	 * @see ChangeOp::getModuleName()
	 */
	public function getModuleName() {
		return null;
	}

}
