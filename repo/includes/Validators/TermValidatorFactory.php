<?php

namespace Wikibase\Validators;

use InvalidArgumentException;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Provides validators for terms (like the maximum length of labels, etc).
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
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
	private $languages;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @param int $maxLength The maximum length of terms.
	 * @param string[] $languages A list of valid language codes
	 * @param EntityIdParser $idParser
	 * @param LabelDescriptionDuplicateDetector $termDuplicateDetector
	 * @param SiteLinkLookup $siteLinkLookup
	 *
	 * @throws \InvalidArgumentException
	 */
	function __construct(
		$maxLength,
		array $languages,
		EntityIdParser $idParser,
		LabelDescriptionDuplicateDetector $termDuplicateDetector,
		SiteLinkLookup $siteLinkLookup
	) {
		if ( !is_int( $maxLength ) || $maxLength <= 0 ) {
			throw new \InvalidArgumentException( '$maxLength must be a positive integer.' );
		}

		$this->maxLength = $maxLength;
		$this->languages = $languages;
		$this->idParser = $idParser;
		$this->termDuplicateDetector = $termDuplicateDetector;
		$this->siteLinkLookup = $siteLinkLookup;
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
	 * @throws InvalidArgumentException
	 * @return FingerprintValidator
	 */
	public function getFingerprintValidator( $entityType ) {
		//TODO: Make this configurable. Use a builder. Allow more types to register.

		switch ( $entityType ) {
			case Item::ENTITY_TYPE:
				return new LabelDescriptionUniquenessValidator( $this->termDuplicateDetector );

			default:
				return new CompositeFingerprintValidator( array() );
		}
	}

	/**
	 * @param string $entityType
	 *
	 * @return ValueValidator
	 */
	public function getLabelValidator( $entityType ) {
		$validators = $this->getCommonTermValidators();

		//TODO: Make this configurable. Use a builder. Allow more types to register.
		if ( $entityType === Property::ENTITY_TYPE ) {
			$validators[] = new NotEntityIdValidator( $this->idParser, 'label-no-entityid', array( Property::ENTITY_TYPE ) );
		}

		return new CompositeValidator( $validators, true );
	}

	/**
	 * @param string $entityType
	 *
	 * @return ValueValidator
	 */
	public function getDescriptionValidator( $entityType ) {
		$validators = $this->getCommonTermValidators();

		return new CompositeValidator( $validators, true );
	}

	/**
	 * @param string $entityType
	 *
	 * @return ValueValidator
	 */
	public function getAliasValidator( $entityType ) {
		$validators = $this->getCommonTermValidators();

		return new CompositeValidator( $validators, true );
	}

	/**
	 * @return ValueValidator[]
	 */
	protected function getCommonTermValidators() {
		$validators = array();
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 1, $this->maxLength, 'mb_strlen' );
		$validators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.

		return $validators;
	}

	/**
	 * @return ValueValidator
	 */
	public function getLanguageValidator() {
		$validators = array();
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new MembershipValidator( $this->languages, 'not-a-language' );

		$validator = new CompositeValidator( $validators, true ); //Note: each validator is fatal
		return $validator;
	}

}
