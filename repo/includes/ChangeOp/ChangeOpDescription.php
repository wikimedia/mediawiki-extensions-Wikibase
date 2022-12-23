<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * Class for description change operation
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class ChangeOpDescription extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var string|null
	 */
	private $description;

	/**
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	/**
	 * @param string $languageCode
	 * @param string|null $description
	 * @param TermValidatorFactory $termValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$languageCode,
		$description,
		TermValidatorFactory $termValidatorFactory
	) {
		if ( !is_string( $languageCode ) ) {
			throw new InvalidArgumentException( 'Language code needs to be a string.' );
		}

		$this->languageCode = $languageCode;
		$this->description = $description;
		$this->termValidatorFactory = $termValidatorFactory;
	}

	/**
	 * Applies the change to the descriptions
	 *
	 * @param TermList $descriptions
	 */
	private function updateDescriptions( TermList $descriptions ) {
		if ( $this->description === null ) {
			$descriptions->removeByLanguage( $this->languageCode );
		} else {
			$descriptions->setTextForLanguage( $this->languageCode, $this->description );
		}
	}

	/**
	 * @param EntityId|null $entityId
	 * @param Term|null $oldDescription
	 * @param Term|null $newDescription
	 * @return ChangeOpDescriptionResult
	 */
	private function buildResult( EntityId $entityId = null, Term $oldDescription = null, Term $newDescription = null ) {

		$isEntityChanged = false;
		$oldDescriptionText = $oldDescription ? $oldDescription->getText() : '';
		$newDescriptionText = $newDescription ? $newDescription->getText() : '';

		if ( $newDescription ) {
			$isEntityChanged = !$newDescription->equals( $oldDescription );
		} elseif ( $oldDescription ) {
			// $newDescription is null, but $oldDescription is not so entity has changed for sure
			$isEntityChanged = true;
		}

		return new ChangeOpDescriptionResult( $entityId, $this->languageCode, $oldDescriptionText, $newDescriptionText, $isEntityChanged );
	}

	/**
	 * @see ChangeOp::apply()
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof DescriptionsProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a DescriptionsProvider' );
		}

		$descriptions = $entity->getDescriptions();

		if ( $descriptions->hasTermForLanguage( $this->languageCode ) ) {
			if ( $this->description === null ) {
				$removedDescription = $descriptions->getByLanguage( $this->languageCode )->getText();
				$this->updateSummary( $summary, 'remove', $this->languageCode, $removedDescription );
			} else {
				$this->updateSummary( $summary, 'set', $this->languageCode, $this->description );
			}

			$oldDescription = $descriptions->getByLanguage( $this->languageCode );
		} else {
			$oldDescription = null;
			$this->updateSummary( $summary, 'add', $this->languageCode, $this->description );
		}

		$this->updateDescriptions( $descriptions );

		if ( $descriptions->hasTermForLanguage( $this->languageCode ) ) {
			$newDescription = $descriptions->getByLanguage( $this->languageCode );
		} else {
			$newDescription = null;
		}

		return $this->buildResult( $entity->getId(), $oldDescription, $newDescription );
	}

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return Result
	 */
	public function validate( EntityDocument $entity ) {
		if ( !( $entity instanceof DescriptionsProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a DescriptionsProvider' );
		}

		$languageValidator = $this->termValidatorFactory->getDescriptionLanguageValidator();
		$termValidator = $this->termValidatorFactory->getDescriptionValidator();

		// check that the language is valid
		$result = $languageValidator->validate( $this->languageCode );

		if ( $result->isValid() && $this->description !== null ) {
			// Check that the new description is valid
			$result = $termValidator->validate( $this->description );
		}

		if ( !$result->isValid() ) {
			return $result;
		}

		if ( $entity instanceof LabelsProvider ) {
			$validator = $this->termValidatorFactory->getLabelDescriptionNotEqualValidator();

			// Check if the new fingerprint of the entity is valid
			$descriptions = clone $entity->getDescriptions();
			$this->updateDescriptions( $descriptions );

			// Noop action, don't try to validate it yet -- T222621
			if ( $descriptions->toTextArray() === $entity->getDescriptions()->toTextArray() ) {
				return $result;
			}

			$result = $validator->validateLabelAndDescription(
				$entity->getLabels(),
				$descriptions,
				[ $this->languageCode ]
			);
		}

		return $result;
	}

	/**
	 * @see ChangeOp::getActions
	 *
	 * @return string[]
	 */
	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT_TERMS ];
	}

}
