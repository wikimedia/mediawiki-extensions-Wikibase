<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Summary;

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
		} else {
			$this->updateSummary( $summary, 'add', $this->languageCode, $this->description );
		}

		$this->updateDescriptions( $descriptions );
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

		$languageValidator = $this->termValidatorFactory->getLanguageValidator();
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

		// TODO: Don't bind against LabelsProvider here, rather use general builders for validators
		if ( $entity instanceof LabelsProvider ) {
			$fingerprintValidator = $this->termValidatorFactory->getFingerprintValidator( $entity->getType() );

			// Check if the new fingerprint of the entity is valid (e.g. if the combination
			// of label and description  is still unique)
			$descriptions = clone $entity->getDescriptions();
			$this->updateDescriptions( $descriptions );

			$result = $fingerprintValidator->validateFingerprint(
				$entity->getLabels(),
				$descriptions,
				$entity->getId(),
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
