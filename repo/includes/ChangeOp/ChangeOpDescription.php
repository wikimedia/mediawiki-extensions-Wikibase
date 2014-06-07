<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\Summary;
use Wikibase\Validators\TermValidatorFactory;

/**
 * Class for description change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpDescription extends ChangeOpBase {

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
	protected $description;

	/**
	 * @since 0.5
	 *
	 * @var TermValidatorFactory
	 */
	protected $termValidatorFactory;

	/**
	 * @since 0.4
	 *
	 * @param string $language
	 * @param string|null $description
	 *
	 * @param TermValidatorFactory $termValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$language,
		$description,
		TermValidatorFactory $termValidatorFactory
	) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		$this->language = $language;
		$this->description = $description;

		$this->termValidatorFactory = $termValidatorFactory;
	}

	/**
	 * Applies the change to the fingerprint
	 *
	 * @param Fingerprint $fingerprint
	 */
	private function updateFingerprint( Fingerprint $fingerprint ) {
		if ( $this->description === null ) {
			$fingerprint->removeDescription( $this->language );
		} else {
			$fingerprint->getDescriptions()->setTextForLanguage( $this->language, $this->description );
		}
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$fingerprint = $entity->getFingerprint();
		$exists = $fingerprint->getDescriptions()->hasTermForLanguage( $this->language );

		if ( $this->description === null ) {
			if ( $exists ) {
				$old = $fingerprint->getDescription( $this->language )->getText();
				$this->updateSummary( $summary, 'remove', $this->language, $old );
			}
		} else {
			if ( $exists ) {
				$fingerprint->getDescription( $this->language );
				$this->updateSummary( $summary, 'set', $this->language, $this->description );
			} else {
				$this->updateSummary( $summary, 'add', $this->language, $this->description );
			}
		}

		$this->updateFingerprint( $fingerprint );
		$entity->setFingerprint( $fingerprint );

		return true;
	}

	/**
	 * Validates this ChangeOp
	 *
	 * @see ChangeOp::validate()
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public function validate( Entity $entity ) {
		$languageValidator = $this->termValidatorFactory->getLanguageValidator();
		$termValidator = $this->termValidatorFactory->getDescriptionValidator( $entity->getType() );
		$fingerprintValidator = $this->termValidatorFactory->getFingerprintValidator( $entity->getType() );

		// check that the language is valid
		$result = $languageValidator->validate( $this->language );

		if ( $result->isValid() && $this->description !== null ) {
			// Check that the new description is valid
			$result = $termValidator->validate( $this->description );
		}

		if ( !$result->isValid() ) {
			return $result;
		}

		// Check if the new fingerprint of the entity is valid (e.g. if the combination
		// of label and description  is still unique)
		$fingerprint = clone $entity->getFingerprint();
		$this->updateFingerprint( $fingerprint );

		$result = $fingerprintValidator->validateFingerprint(
			$fingerprint,
			$entity->getId(),
			array( $this->language )
		);

		return $result;
	}
}
