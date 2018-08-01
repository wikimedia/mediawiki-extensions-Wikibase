<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\LabelDescriptionDuplicateDetector;

/**
 * Provides validators for terms (like the maximum length of labels, etc).
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TermValidatorFactory {

	/**
	 * @var int
	 */
	private $maxLength;

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	private $duplicateDetector;

	/**
	 * @param int $maxLength The maximum length of terms.
	 * @param string[] $languageCodes A list of valid language codes
	 * @param EntityIdParser $idParser
	 * @param LabelDescriptionDuplicateDetector $duplicateDetector
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$maxLength,
		array $languageCodes,
		EntityIdParser $idParser,
		LabelDescriptionDuplicateDetector $duplicateDetector
	) {
		if ( !is_int( $maxLength ) || $maxLength <= 0 ) {
			throw new InvalidArgumentException( '$maxLength must be a positive integer.' );
		}

		$this->maxLength = $maxLength;
		$this->languageCodes = $languageCodes;
		$this->idParser = $idParser;
		$this->duplicateDetector = $duplicateDetector;
	}

	/**
	 * Returns a validator for checking an (updated) fingerprint.
	 * May be used to apply global uniqueness checks.
	 *
	 * @note The fingerprint validator provided here is intended to apply
	 *       checks in ADDITION to the ones performed by the validators
	 *       returned by the getLabelValidator() etc functions below.
	 *
	 * @param string $entityType
	 *
	 * @return FingerprintValidator
	 */
	public function getFingerprintValidator( $entityType ) {
		//TODO: Make this configurable. Use a builder. Allow more types to register.

		switch ( $entityType ) {
			case Item::ENTITY_TYPE:
				return new LabelDescriptionUniquenessValidator( $this->duplicateDetector );

			default:
				return new NullFingerprintValidator();
		}
	}

	/**
	 * @param string $entityType
	 *
	 * @return ValueValidator
	 */
	public function getLabelValidator( $entityType ) {
		$validators = $this->getCommonTermValidators( 'label-' );

		//TODO: Make this configurable. Use a builder. Allow more types to register.
		if ( $entityType === Property::ENTITY_TYPE ) {
			$validators[] = new NotEntityIdValidator( $this->idParser, 'label-no-entityid', [ Property::ENTITY_TYPE ] );
		}

		return new CompositeValidator( $validators, true );
	}

	/**
	 * @return ValueValidator
	 */
	public function getDescriptionValidator() {
		$validators = $this->getCommonTermValidators( 'description-' );

		return new CompositeValidator( $validators, true );
	}

	/**
	 * @param string $entityType
	 *
	 * @return ValueValidator
	 */
	public function getAliasValidator( $entityType ) {
		$validators = $this->getCommonTermValidators( 'alias-' );

		return new CompositeValidator( $validators, true );
	}

	/**
	 * @return ValueValidator[]
	 */
	private function getCommonTermValidators( $errorCodePrefix ) {
		$validators = [];
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 1, $this->maxLength, 'mb_strlen', $errorCodePrefix );
		// no leading/trailing whitespace, no tab or vertical whitespace, no line breaks.
		$validators[] = new RegexValidator( '/^\s|[\v\t]|\s$/u', true );

		return $validators;
	}

	/**
	 * @return ValueValidator
	 */
	public function getLanguageValidator() {
		$validators = [];
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new MembershipValidator( $this->languageCodes, 'not-a-language' );

		$validator = new CompositeValidator( $validators, true ); //Note: each validator is fatal
		return $validator;
	}

}
