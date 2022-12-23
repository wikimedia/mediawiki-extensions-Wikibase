<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use MediaWiki\Languages\LanguageNameUtils;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;

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
	 * @var TermsCollisionDetectorFactory
	 */
	private $termsCollisionDetectorFactory;

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var LanguageNameUtils
	 */
	private $languageNameUtils;

	/**
	 * @param int $maxLength The maximum length of terms.
	 * @param string[] $languageCodes A list of valid language codes
	 * @param EntityIdParser $idParser
	 * @param TermsCollisionDetectorFactory $termsCollisionDetectorFactory
	 * @param TermLookup $termLookup
	 * @param LanguageNameUtils $languageNameUtils
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$maxLength,
		array $languageCodes,
		EntityIdParser $idParser,
		TermsCollisionDetectorFactory $termsCollisionDetectorFactory,
		TermLookup $termLookup,
		LanguageNameUtils $languageNameUtils
	) {
		if ( !is_int( $maxLength ) || $maxLength <= 0 ) {
			throw new InvalidArgumentException( '$maxLength must be a positive integer.' );
		}

		$this->maxLength = $maxLength;
		$this->languageCodes = $languageCodes;
		$this->idParser = $idParser;
		$this->termsCollisionDetectorFactory = $termsCollisionDetectorFactory;
		$this->termLookup = $termLookup;
		$this->languageNameUtils = $languageNameUtils;
	}

	/**
	 * This function returns a fingerprint uniqueness validator that validates uniqueness in the term store.
	 */
	public function getFingerprintUniquenessValidator( string $entityType ): ?ValueValidator {
		if ( in_array( $entityType, [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ] ) ) {
			return new FingerprintUniquenessValidator(
				$this->termsCollisionDetectorFactory->getTermsCollisionDetector( $entityType ),
				$this->termLookup
			);
		}

		return null;
	}

	/**
	 * Returns a validator for checking distinctness of labels & descriptions
	 *
	 * @note The validator provided here is intended to apply
	 *       checks in ADDITION to the ones performed by the validators
	 *       returned by the getLabelValidator() etc functions below.
	 *
	 * @return LabelDescriptionNotEqualValidator
	 */
	public function getLabelDescriptionNotEqualValidator() {
		return new LabelDescriptionNotEqualValidator();
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
	 * @return ValueValidator
	 */
	public function getAliasValidator() {
		$validators = $this->getCommonTermValidators( 'alias-' );

		return new CompositeValidator( $validators, true );
	}

	/**
	 * @param string $errorCodePrefix
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

	public function getLabelLanguageValidator(): ValueValidator {
		return new CompositeValidator( $this->getLanguageValidators(), true ); //Note: each validator is fatal
	}

	public function getDescriptionLanguageValidator(): ValueValidator {
		$validators = $this->getLanguageValidators();
		$validators[] = new NotMulValidator( $this->languageNameUtils );

		return new CompositeValidator( $validators, true ); //Note: each validator is fatal
	}

	public function getAliasLanguageValidator(): ValueValidator {
		return new CompositeValidator( $this->getLanguageValidators(), true ); //Note: each validator is fatal
	}

	/**
	 * @return ValueValidator[]
	 */
	private function getLanguageValidators(): array {
		$validators = [];
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new MembershipValidator( $this->languageCodes, 'not-a-language' );

		return $validators;
	}

	public function getLabelUniquenessValidator( $entityType ): LabelUniquenessValidator {
		return new LabelUniquenessValidator(
			$this->termsCollisionDetectorFactory->getTermsCollisionDetector( $entityType )
		);
	}

}
