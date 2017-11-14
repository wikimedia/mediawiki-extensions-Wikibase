<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Summary;

/**
 * Class for holding a batch of change operations
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Kreuz
 */
class ChangeOps implements ChangeOp {

	/**
	 * @var ChangeOp[]
	 */
	private $changeOps = [];

	/**
	 * @param ChangeOp|ChangeOp[] $changeOps
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $changeOps = [] ) {
		$this->add( $changeOps );
	}

	/**
	 * Adds one change operation or a list of change operations.
	 *
	 * @param ChangeOp|ChangeOp[] $changeOps
	 *
	 * @throws InvalidArgumentException
	 */
	public function add( $changeOps ) {
		if ( !is_array( $changeOps ) ) {
			$changeOps = [ $changeOps ];
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
	 * @return ChangeOp[]
	 */
	public function getChangeOps() {
		return $this->changeOps;
	}

	/**
	 * Applies all changes to the given entity
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( count( $this->changeOps ) === 1 ) {
			reset( $this->changeOps )->apply( $entity, $summary );
		} elseif ( count( $this->changeOps ) === 0 ) {
			return;
		} else {
			foreach ( $this->changeOps as $changeOp ) {
				$changeOp->apply( $entity, null );
			}
			if ( $summary ) {
				$summary->setAction( 'update' );
			}
		}
	}

	/**
	 * @see ChangeOp::validate()
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
	 * @see ChangeOp::getActions
	 *
	 * @return string[]
	 */
	public function getActions() {
		return array_unique( array_reduce(
			$this->changeOps,
			function( array $actions, ChangeOp $changeOp ) {
				return array_merge( $actions, $changeOp->getActions() );
			},
			[]
		) );
	}

}
