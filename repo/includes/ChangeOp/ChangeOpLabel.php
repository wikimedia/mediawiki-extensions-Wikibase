<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Summary;

/**
 * Class for label change operation
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 * @author Thiemo Mättig
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
	 * @since 0.5
	 *
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
		} else {
			$this->updateSummary( $summary, 'add', $this->languageCode, $this->label );
		}

		$this->updateLabels( $labels );
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

		$languageValidator = $this->termValidatorFactory->getLanguageValidator();
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

		// TODO: Don't bind against Fingerprint here, rather use general builders for validators
		if ( $entity instanceof FingerprintProvider ) {
			$fingerprintValidator = $this->termValidatorFactory->getFingerprintValidator( $entity->getType() );

			// Check if the new fingerprint of the entity is valid (e.g. if the label is unique)
			$fingerprint = clone $entity->getFingerprint();
			$this->updateLabels( $fingerprint->getLabels() );

			$result = $fingerprintValidator->validateFingerprint(
				$fingerprint,
				$entity->getId(),
				array( $this->languageCode )
			);
		}

		return $result;
	}

	/**
	 * @see ChangeOp::getModuleName()
	 */
	public function getModuleName() {
		return 'wbsetlabel';
	}

}
