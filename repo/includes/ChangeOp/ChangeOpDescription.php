<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueValidators\ValueValidator;
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
	 * @var ValueValidator
	 */
	protected $languageValidator;

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
			$fingerprint->setDescription( new Term( $this->language, $this->description ) );
		}
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$this->validateChange( $entity );

		$fingerprint = $entity->getFingerprint();

		if ( $this->description === null ) {
			$this->updateSummary( $summary, 'remove', $this->language, $fingerprint->getDescription( $this->language )->getText() );
		} else {
			try {
				$fingerprint->getDescription( $this->language );
				$action = 'set';
			} catch ( OutOfBoundsException $ex ) {
				$action = 'add';
			}

			$this->updateSummary( $summary, $action, $this->language, $this->description );
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
		$termValidator = $this->termValidatorFactory->getDescriptionValidator( $entity->getType() );
		$fingerprintValidator = $this->termValidatorFactory->getFingerprintValidator( $entity->getType() );

		// check that the language is valid (note that it is fine to remove bad languages)
		$this->applyValueValidator( $languageValidator, $this->language );

		if ( $this->description !== null ) {
			// Check that the new label is valid
			$this->applyValueValidator( $termValidator, $this->description );
		}

		// Check if the new fingerprint of the entity is valid (e.g. if the combination
		// of label and description  is still unique)
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
