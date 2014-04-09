<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Status;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
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

	/**
	 * @note: It seems like we could use a TermValidatorFactory, but let's
	 *        postpone that for when we are moving validation to the ChangeOps.
	 *
	 * @param TermDuplicateDetector $duplicateDetector
	 * @param EntityIdParser $idParser
	 * @param ValueValidator $languageValidator
	 * @param ValueValidator $labelValidator
	 * @param ValueValidator $descriptionValidator
	 * @param ValueValidator $aliasValidator
	 * @param ValidatorErrorLocalizer $validatorErrorLocalizer
	 */
	public function __construct(
		TermDuplicateDetector $duplicateDetector,
		EntityIdParser $idParser,
		ValueValidator $languageValidator,
		ValueValidator $labelValidator,
		ValueValidator $descriptionValidator,
		ValueValidator $aliasValidator,
		ValidatorErrorLocalizer $validatorErrorLocalizer
	) {
		$this->duplicateDetector = $duplicateDetector;
		$this->idParser = $idParser;
		$this->languageValidator = $languageValidator;
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
		$labelLanguagesToCheck = $this->getLanguagesToCheck( $entityDiff->getLabelsDiff() );
		$descriptionLanguagesToCheck = $this->getLanguagesToCheck( $entityDiff->getDescriptionsDiff() );
		$aliasLanguagesToCheck = $this->getLanguagesToCheck( $entityDiff->getAliasesDiff() );

		$status = Status::newGood();

		try {
			$this->checkTerms(
				$labelLanguagesToCheck,
				$this->languageValidator
			);

			$this->checkTerms(
				$descriptionLanguagesToCheck,
				$this->languageValidator
			);

			$this->checkTerms(
				$aliasLanguagesToCheck,
				$this->languageValidator
			);

			$this->checkTerms(
				$entity->getLabels( $labelLanguagesToCheck ),
				$this->labelValidator
			);

			$this->checkTerms(
				$entity->getDescriptions( $descriptionLanguagesToCheck ),
				$this->descriptionValidator
			);

			$this->checkTerms(
				$entity->getAllAliases( $aliasLanguagesToCheck ),
				$this->aliasValidator
			);

			if ( $entity->getType() === Property::ENTITY_TYPE ) {
				$this->checkLabelEntityIdConflicts(
					$entity->getLabels( $labelLanguagesToCheck ),
					Property::ENTITY_TYPE
				);
			}

			if ( count( $labelLanguagesToCheck )
				|| count( $descriptionLanguagesToCheck ) ) {

				$this->checkTermDuplicates( $entity );
			}
		} catch ( ChangeOpValidationException $ex ) {
			// NOTE: We use a ChangeOpValidationException here, since we plan
			// to move the validation into the ChangeOps anyway.
			$status = $this->resultToStatus( $ex->getValidationResult() );
		}

		return $status;
	}

	/**
	 * @param Entity $entity
	 *
	 * @throws ChangeOp\ChangeOpValidationException
	 */
	private function checkTermDuplicates( Entity $entity ) {
		if ( $entity->getType() === Property::ENTITY_TYPE ) {
			//FIXME: This is redundant, since it's also checked on every save as a hard constraint.
			//       We need a single place to define hard and soft contraints.
			$result = $this->duplicateDetector->detectLabelConflictsForEntity( $entity );
		} else {
			$result = $this->duplicateDetector->detectLabelDescriptionConflictsForEntity( $entity );
		}

		if ( !$result->isValid() ) {
			throw new ChangeOpValidationException( $result );
		}
	}

	/**
	 * Checks a list of terms using the given validator.
	 * If the entries in the list are arrays, the check is performed recursively.
	 *
	 * @param string[]|string[][] $terms
	 * @param ValueValidator $validator
	 *
	 * @throws ChangeOp\ChangeOpValidationException
	 */
	private function checkTerms( array $terms, ValueValidator $validator ) {
		foreach ( $terms as $term ) {
			if ( is_array( $term ) ) {
				// check recursively
				$this->checkTerms( $term, $validator );
			} else {
				$result = $this->aliasValidator->validate( $term );

				if ( !$result->isValid() ) {
					throw new ChangeOpValidationException( $result );
				}
			}
		}
	}

	/**
	 * Gets the keys of all additions and changes in the diff.
	 * Removals are ignored.
	 *
	 * @param Diff $diff
	 *
	 * @return string[]|int[]
	 */
	private function getLanguagesToCheck( Diff $diff ) {
		return array_unique( array_merge(
			array_keys( $diff->getAdditions() ),
			array_keys( $diff->getChanges() )
		) );
	}

	/**
	 * @todo: Fold this into the ValidatorErrorLocalizer interface!
	 *
	 * @param Result $result
	 *
	 * @return Status
	 */
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

	/**
	 * Checks that the given labels are nto valid entity IDs of the forbidden type.
	 *
	 * @todo Factor this check into a separate ValueValidtor.
	 *
	 * @since 0.5
	 *
	 * @param string[] $labels
	 * @param string $forbiddenEntityType entity type that should lead to a conflict
	 *
	 * @throws ChangeOp\ChangeOpValidationException
	 */
	protected function checkLabelEntityIdConflicts( array $labels, $forbiddenEntityType ) {

		foreach ( $labels as $labelText ) {
			try {
				$entityId = $this->idParser->parse( $labelText );
				if ( $entityId->getEntityType() === $forbiddenEntityType ) {
					// The label is a valid ID - we don't like that!
					$error = Error::newError( 'Label looks like an Entity ID!', 'label', 'label-no-entityid', $labelText );
					$result = Result::newError( array( $error ) );
					throw new ChangeOpValidationException( $result );
				}
			} catch ( EntityIdParsingException $parseException ) {
				// All fine, the parsing did not work, so there is no entity id :)
			}
		}
	}
}