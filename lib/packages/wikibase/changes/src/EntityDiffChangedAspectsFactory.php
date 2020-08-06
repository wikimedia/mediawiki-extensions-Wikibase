<?php

namespace Wikibase\Lib\Changes;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityDiffChangedAspectsFactory {

	/** @var LoggerInterface */
	private $logger;

	public function __construct( ?LoggerInterface $logger = null ) {
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * @param Diff $entityDiff
	 *
	 * @return EntityDiffChangedAspects
	 */
	public function newFromEntityDiff( Diff $entityDiff ) {
		$labelChanges = [];
		$descriptionChanges = [];
		$statementChanges = [];
		$siteLinkChanges = [];
		$otherChanges = false;

		$remainingDiffOps = count( $entityDiff ); // this is a "deep" count!

		if ( $entityDiff instanceof ItemDiff && !$entityDiff->getSiteLinkDiff()->isEmpty() ) {
			$siteLinkDiff = $entityDiff->getSiteLinkDiff();

			$remainingDiffOps -= count( $siteLinkDiff );
			$siteLinkChanges = $this->getChangedSiteLinks( $siteLinkDiff );
		}

		if ( $entityDiff instanceof EntityDiff ) {
			$labelsDiff = $entityDiff->getLabelsDiff();
			if ( !empty( $labelsDiff ) ) {
				$remainingDiffOps -= count( $labelsDiff );
				$labelChanges = $this->getChangedLabels( $labelsDiff );
			}

			$descriptionsDiff = $entityDiff->getDescriptionsDiff();
			if ( !empty( $descriptionsDiff ) ) {
				$remainingDiffOps -= count( $descriptionsDiff );
				$descriptionChanges = $this->getChangedDescriptions( $descriptionsDiff );
			}

			$claimsDiff = $entityDiff->getClaimsDiff();
			if ( !empty( $claimsDiff ) ) {
				$remainingDiffOps -= count( $claimsDiff );
				$statementChanges = $this->getChangedStatements( $claimsDiff );
			}
		}

		if ( $remainingDiffOps > 0 ) {
			$otherChanges = true;
		}

		return new EntityDiffChangedAspects(
			$labelChanges,
			$descriptionChanges,
			$statementChanges,
			$siteLinkChanges,
			$otherChanges
		);
	}

	/**
	 * @param Diff $siteLinkDiff
	 *
	 * @return array[]
	 */
	private function getChangedSiteLinks( Diff $siteLinkDiff ) {
		$siteLinkChanges = [];

		foreach ( $siteLinkDiff as $siteId => $diffPerSite ) {
			$siteLinkChanges[$siteId] = $this->getSiteLinkChangePerSite( $diffPerSite );
		}

		return $siteLinkChanges;
	}

	/**
	 * @param DiffOp $siteLinkDiffOp
	 *
	 * @return array ( string|null $oldPageName, string|null $newPageName, bool $badgesChanged )
	 * @phan-return array{0:?string,1:?string,2:bool}
	 */
	private function getSiteLinkChangePerSite( DiffOp $siteLinkDiffOp ) {
		if ( !$siteLinkDiffOp instanceof Diff ) {
			return [ null, null, false ];
		}

		$removals = $siteLinkDiffOp->getRemovedValues();
		$additions = $siteLinkDiffOp->getAddedValues();
		$changes = $siteLinkDiffOp->getChanges();

		$oldValue = ( array_key_exists( 'name', $removals ) ) ? $removals['name'] : null;
		$newValue = ( array_key_exists( 'name', $additions ) ) ? $additions['name'] : null;

		if ( array_key_exists( 'name', $changes ) ) {
			$oldValue = $changes['name']->getOldValue();
			$newValue = $changes['name']->getNewValue();
		}

		return [ $oldValue, $newValue, $siteLinkDiffOp->offsetExists( 'badges' ) ];
	}

	/**
	 * @param Diff $labelsDiff
	 *
	 * @return string[]
	 */
	private function getChangedLabels( Diff $labelsDiff ) {
		return array_keys( iterator_to_array( $labelsDiff ) );
	}

	/**
	 * @param Diff $descriptionsDiff
	 *
	 * @return string[]
	 */
	private function getChangedDescriptions( Diff $descriptionsDiff ) {
		return array_keys( iterator_to_array( $descriptionsDiff ) );
	}

	/**
	 * @param Diff $claimsDiff
	 *
	 * @return string[]
	 */
	private function getChangedStatements( Diff $claimsDiff ) {
		$changedStatements = [];

		foreach ( $claimsDiff as $pid => $diffOp ) {
			/** @var Statement $statement */
			if ( $diffOp instanceof DiffOpAdd ) {
				$statement = $diffOp->getNewValue();
			} elseif ( $diffOp instanceof DiffOpRemove ) {
				$statement = $diffOp->getOldValue();
			} elseif ( $diffOp instanceof DiffOpChange ) {
				$statement = $diffOp->getOldValue();
				/** @var $newStatement Statement */
				$newStatement = $diffOp->getNewValue();

				$changedStatements[] = $newStatement->getPropertyId()->getSerialization();
			} else {
				$this->logger->warning( 'Unknown DiffOp type {class}', [
					'class' => get_class( $diffOp ),
				] );
				continue;
			}

			'@phan-var Statement $statement';
			$changedStatements[] = $statement->getPropertyId()->getSerialization();
		}

		return array_values( array_unique( $changedStatements ) );
	}

	/**
	 * @return EntityDiffChangedAspects
	 */
	public function newEmpty() {
		return $this->newFromEntityDiff( new Diff() );
	}
}
