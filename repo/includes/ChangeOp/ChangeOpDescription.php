<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Summary;

/**
 * Class for description change operation
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 * @author Thiemo Mättig
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
	 * @since 0.4
	 *
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

		// TODO: Don't bind against Fingerprint here, rather use general builders for validators
		if ( $entity instanceof FingerprintProvider ) {
			$fingerprintValidator = $this->termValidatorFactory->getFingerprintValidator( $entity->getType() );

			// Check if the new fingerprint of the entity is valid (e.g. if the combination
			// of label and description  is still unique)
			$fingerprint = clone $entity->getFingerprint();
			$this->updateDescriptions( $fingerprint->getDescriptions() );

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
		return 'wbsetdescription';
	}

}
