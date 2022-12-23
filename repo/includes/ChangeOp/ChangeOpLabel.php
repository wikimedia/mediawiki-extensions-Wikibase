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
 * Class for label change operation
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class ChangeOpLabel extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var string|null
	 */
	private $label;

	/**
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	/**
	 * @param string $languageCode
	 * @param string|null $label
	 * @param TermValidatorFactory $termValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$languageCode,
		$label,
		TermValidatorFactory $termValidatorFactory
	) {
		if ( !is_string( $languageCode ) ) {
			throw new InvalidArgumentException( 'Language code needs to be a string.' );
		}

		$this->languageCode = $languageCode;
		$this->label = $label;
		$this->termValidatorFactory = $termValidatorFactory;
	}

	/**
	 * Applies the change to the labels
	 *
	 * @param TermList $labels
	 */
	private function updateLabels( TermList $labels ) {
		if ( $this->label === null ) {
			$labels->removeByLanguage( $this->languageCode );
		} else {
			$labels->setTextForLanguage( $this->languageCode, $this->label );
		}
	}

	/**
	 * @param EntityId|null $entityId
	 * @param Term|null $oldLabel
	 * @param Term|null $newLabel
	 * @return ChangeOpLabelResult
	 */
	private function buildResult( EntityId $entityId = null, Term $oldLabel = null, Term $newLabel = null ) {
		$isEntityChanged = false;
		$oldLabelText = $oldLabel ? $oldLabel->getText() : '';
		$newLabelText = $newLabel ? $newLabel->getText() : '';

		if ( $newLabel ) {
			$isEntityChanged = !$newLabel->equals( $oldLabel );
		} elseif ( $oldLabel ) {
			// $newLabel is null, but $oldDescription is not so entity has changed for sure
			$isEntityChanged = true;
		}

		return new ChangeOpLabelResult( $entityId, $this->languageCode, $oldLabelText, $newLabelText, $isEntityChanged );
	}

	/**
	 * @see ChangeOp::apply()
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof LabelsProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a LabelsProvider' );
		}

		$labels = $entity->getLabels();

		if ( $labels->hasTermForLanguage( $this->languageCode ) ) {
			if ( $this->label === null ) {
				$oldLabel = $labels->getByLanguage( $this->languageCode )->getText();
				$this->updateSummary( $summary, 'remove', $this->languageCode, $oldLabel );
			} else {
				$this->updateSummary( $summary, 'set', $this->languageCode, $this->label );
			}
			$oldLabel = $labels->getByLanguage( $this->languageCode );
		} else {
			$oldLabel = null;
			$this->updateSummary( $summary, 'add', $this->languageCode, $this->label );
		}

		$this->updateLabels( $labels );

		if ( $labels->hasTermForLanguage( $this->languageCode ) ) {
			$newLabel = $labels->getByLanguage( $this->languageCode );
		} else {
			$newLabel = null;
		}

		return $this->buildResult( $entity->getId(), $oldLabel, $newLabel );
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
		if ( !( $entity instanceof LabelsProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a LabelsProvider' );
		}

		$languageValidator = $this->termValidatorFactory->getLabelLanguageValidator();
		$termValidator = $this->termValidatorFactory->getLabelValidator( $entity->getType() );

		// check that the language is valid
		$result = $languageValidator->validate( $this->languageCode );

		if ( $result->isValid() && $this->label !== null ) {
			// Check that the new label is valid
			$result = $termValidator->validate( $this->label );
		}

		if ( !$result->isValid() ) {
			return $result;
		}

		if ( $entity instanceof DescriptionsProvider ) {
			$validator = $this->termValidatorFactory->getLabelDescriptionNotEqualValidator();

			// Check if the new fingerprint of the entity is valid
			$labels = clone $entity->getLabels();
			$this->updateLabels( $labels );

			// Noop action, don't try to validate it yet -- T222621
			if ( $labels->toTextArray() === $entity->getLabels()->toTextArray() ) {
				return $result;
			}

			$result = $validator->validateLabelAndDescription(
				$labels,
				$entity->getDescriptions(),
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
