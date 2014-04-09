<?php

namespace Wikibase;

use Status;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

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
 * @author Daniel Kinzler
 */
class PreSaveChecks {

	public function __construct(
		TermDuplicateDetector $duplicateDetector,
		ValueValidator $labelValidator,
		ValueValidator $descriptionValidator,
		ValueValidator $aliasValidator,
		ValidatorErrorLocalizer $validatorErrorLocalizer
	) {
		$this->duplicateDetector = $duplicateDetector;
		$this->labelValidator = $labelValidator;
		$this->descriptionValidator = $descriptionValidator;
		$this->aliasValidator = $aliasValidator;
		$this->validatorErrorLocalizer = $validatorErrorLocalizer;
	}

	/**
	 * Implements pre-safe checks.
	 *
	 * @note: This is a preliminary implementation. This entire class will be redundant
	 * once validation is done in ChangeOps.
	 *
	 * @param Entity $entity
	 * @param EntityDiff $entityDiff
	 *
	 * @return Status
	 */
	public function applyPreSaveChecks( Entity $entity, EntityDiff $entityDiff ) {
		$labelDiff = $entityDiff->getLabelsDiff();
		$descriptionDiff = $entityDiff->getDescriptionsDiff();
		$aliasDiff = $entityDiff->getAliasesDiff();

		$result = Result::newSuccess();

		if ( count( $labelDiff->getAdditions() )
			|| count( $descriptionDiff->getAdditions() ) ) {

			if ( $entity->getType() === Property::ENTITY_TYPE ) {
				$result = $this->duplicateDetector->detectLabelConflictsForEntity( $entity );
			} else {
				$result = $this->duplicateDetector->detectLabelDescriptionConflictsForEntity( $entity );
			}
		}

		if ( !$result->isValid() ) {
			return $this->resultToStatus( $result );
		}

		foreach ( $labelDiff as $lang => $op ) {
			$label = $entity->getLabel( $lang );
			$result = $this->labelValidator->validate( $label );

			if ( !$result->isValid() ) {
				return $this->resultToStatus( $result );
			}
		}

		foreach ( $descriptionDiff as $lang => $op ) {
			$description = $entity->getDescription( $lang );
			$result = $this->descriptionValidator->validate( $description );

			if ( !$result->isValid() ) {
				return $this->resultToStatus( $result );
			}
		}

		foreach ( $aliasDiff as $lang => $op ) {
			$aliases = $entity->getAliases( $lang );

			foreach ( $aliases as $alias ) {
				$result = $this->aliasValidator->validate( $alias );

				if ( !$result->isValid() ) {
					return $this->resultToStatus( $result );
				}
			}
		}

		return Status::newGood();
	}

	private function resultToStatus( Result $result ) {
		if ( $result->isValid() ) {
			return Status::newGood();
		}

		$status = Status::newGood();
		$status->setResult( false );

		foreach ( $result->getErrors() as $error ) {
			$message = $this->validatorErrorLocalizer->getErrorMessage( $error );
			$status->error( $message );
		}

		return $status;
	}
}