<?php

namespace Wikibase;

use Status;

/**
 * Encapsulates programmatic checks to perform before checking an item.
 *
 * @todo This was factored out of EditEntity as a quick and dirty measure.
 * The process of enforcing constraints on this level should be re-thought and
 * properly refactored.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class PreSaveChecks {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	/**
	 * Implements pre-safe checks.
	 *
	 * @param Entity $entity
	 * @param EntityDiff $entityDiff
	 *
	 * @return Status
	 */
	public function applyPreSaveChecks( Entity $entity, EntityDiff $entityDiff = null ) {
		$status = Status::newGood();

		$multilangViolationDetector = new MultiLangConstraintDetector();
		$multilangViolationDetector->addConstraintChecks(
			$entity,
			$status,
			$entityDiff
			//TODO: pass the limits from a constructor param
		);

		$dbw = wfGetDB( DB_MASTER );

		// FIXME: Do not run this when running test using MySQL as self joins fail on temporary tables.
		if ( !defined( 'MW_PHPUNIT_TEST' )
			|| !( StoreFactory::getStore() instanceof \Wikibase\SqlStore )
			|| $dbw->getType() !== 'mysql' ) {

			// The below looks for all conflicts and then removes the ones not
			// caused by the edit. This can be improved by only looking for
			// those conflicts that can be caused by the edit.

			$termViolationDetector = new LabelDescriptionDuplicateDetector();

			$termViolationDetector->addLabelDescriptionConflicts(
				$entity,
				$status,
				$this->termIndex,
				$entityDiff === null ? null : $entityDiff->getLabelsDiff(),
				$entityDiff === null ? null : $entityDiff->getDescriptionsDiff()
			);
		}

		return $status;
	}

}