<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\Summary;
use Wikibase\Validators\TermValidatorFactory;

/**
 * Class for label change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpLabel extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * @since 0.4
	 *
	 * @var string|null
	 */
	protected $label;

	/**
	 * @since 0.5
	 *
	 * @var TermValidatorFactory
	 */
	protected $termValidatorFactory;

	/**
	 * @since 0.5
	 *
	 * @param string $language
	 * @param string|null $label
	 *
	 * @param TermValidatorFactory $termValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$language,
		$label,
		TermValidatorFactory $termValidatorFactory
	) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		$this->language = $language;
		$this->label = $label;

		$this->termValidatorFactory = $termValidatorFactory;
	}

	/**
	 * Applies the change to the fingerprint
	 *
	 * @param Fingerprint $fingerprint
	 */
	private function updateFingerprint( Fingerprint $fingerprint ) {
		if ( $this->label === null ) {
			$fingerprint->removeLabel( $this->language );
		} else {
			$fingerprint->setLabel( new Term( $this->language, $this->label ) );
		}
	}

	/**
	 * @see ChangeOp::apply()
	 *
	 * @param Entity $entity
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 * @return bool
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$this->validateChange( $entity );

		$fingerprint = $entity->getFingerprint();

		if ( $this->label === null ) {
			try {
				$old = $fingerprint->getLabel( $this->language )->getText();
				$this->updateSummary( $summary, 'remove', $this->language, $old );
			} catch ( OutOfBoundsException $ex ) {
				// no-op
			}
		} else {
			try {
				$fingerprint->getLabel( $this->language );
				$this->updateSummary( $summary, 'set', $this->language, $this->label );
			} catch ( OutOfBoundsException $ex ) {
				$this->updateSummary( $summary, 'add', $this->language, $this->label );
			}
		}

		$this->updateFingerprint( $fingerprint );
		$entity->setFingerprint( $fingerprint );

		return true;
	}

	/**
	 * @param Entity $entity
	 *
	 * @throws ChangeOpException
	 */
	protected function validateChange( Entity $entity ) {
		$languageValidator = $this->termValidatorFactory->getLanguageValidator();
		$termValidator = $this->termValidatorFactory->getLabelValidator( $entity->getType() );
		$fingerprintValidator = $this->termValidatorFactory->getFingerprintValidator( $entity->getType() );

		// check that the language is valid (note that it is fine to remove bad languages)
		$this->applyValueValidator( $languageValidator, $this->language );

		if ( $this->label !== null ) {
			// Check that the new label is valid
			$this->applyValueValidator( $termValidator, $this->label );
		}

		// Check if the new fingerprint of the entity is valid (e.g. if the label is unique)
		$fingerprint = $entity->getFingerprint();
		$this->updateFingerprint( $fingerprint );

		$result = $fingerprintValidator->validateFingerprint(
			$fingerprint,
			$entity->getId(),
			array( $this->language )
		);

		if ( !$result->isValid() ) {
			throw new ChangeOpValidationException( $result );
		}
	}
}
