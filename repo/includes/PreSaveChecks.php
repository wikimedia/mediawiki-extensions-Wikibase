<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Status;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\Validators\EntityValidator;
use Wikibase\Validators\TermValidatorFactory;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Encapsulates programmatic checks to perform before checking an item.
 *
 * @todo: Factor uniqueness checks into ChangeOps. Needs batching!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PreSaveChecks {

	/**
	 * @param TermValidatorFactory $termValidatorFactory
	 * @param ValidatorErrorLocalizer $validatorErrorLocalizer
	 */
	public function __construct(
		TermValidatorFactory $termValidatorFactory,
		ValidatorErrorLocalizer $validatorErrorLocalizer
	) {
		$this->termValidatorFactory = $termValidatorFactory;
		$this->validatorErrorLocalizer = $validatorErrorLocalizer;
	}

	/**
	 * Implements pre-safe checks. Currently, this enforces that the combination of label
	 * and description of an item in a given language is unique for that language.
	 *
	 * @note: "local" validation of labels, descriptions and aliases is done in the respective
	 *        ChangeOps.
	 * @note: uniqueness of sitelinks and property labels are hard constraints and are enforced
	 *        via EntityContent::prepareEdit.
	 *
	 * @param Entity $entity
	 * @param EntityDiff $entityDiff
	 *
	 * @return Status
	 */
	public function applyPreSaveChecks( Entity $entity, EntityDiff $entityDiff = null ) {
		if ( $entityDiff ) {
			$labelLanguagesToCheck = $this->getLanguagesToCheck( $entityDiff->getLabelsDiff() );
			$descriptionLanguagesToCheck = $this->getLanguagesToCheck( $entityDiff->getDescriptionsDiff() );
		} else {
			$labelLanguagesToCheck = array_keys( $entity->getLabels() );
			$descriptionLanguagesToCheck = array_keys( $entity->getDescriptions() );
		}

		$entityType = $entity->getType();

		$status = Status::newGood();

		try {
			if ( !empty( $labelLanguagesToCheck ) || !empty( $descriptionLanguagesToCheck ) ) {
				$uniquenessValidator = $this->termValidatorFactory->getUniquenessValidator( $entityType );
				$this->checkEntityConstraint( $entity, $uniquenessValidator );
			}
		} catch ( ChangeOpValidationException $ex ) {
			// NOTE: We use a ChangeOpValidationException here, since we plan
			// to move the validation into the ChangeOps anyway.
			$status = $this->validatorErrorLocalizer->getResultStatus( $ex->getValidationResult() );
		}

		return $status;
	}

	/**
	 * @param Entity $entity
	 * @param EntityValidator $validator
	 *
	 * @throws ChangeOpValidationException
	 */
	private function checkEntityConstraint( Entity $entity, EntityValidator $validator ) {
		$result = $validator->validateEntity( $entity );

		if ( !$result->isValid() ) {
			throw new ChangeOpValidationException( $result );
		}
	}

	/**
	 * Gets the keys of all additions and other changes in the diff.
	 * Removals are ignored.
	 *
	 * @param Diff $diff
	 *
	 * @return string[]|int[]
	 */
	private function getLanguagesToCheck( Diff $diff ) {
		return array_unique( array_diff(
			array_keys( $diff->getOperations() ),
			array_keys( $diff->getRemovals() )
		) );
	}
}