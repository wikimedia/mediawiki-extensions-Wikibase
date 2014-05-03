<?php

namespace Wikibase\Validators;

use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\LabelDescriptionDuplicateDetector;


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
	protected $maxLength;

	/**
	 * @var string[]
	 */
	protected $languages;

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @param int $maxLength The maximum length of terms.
	 * @param string[] $languages A list of valid language codes
	 * @param EntityIdParser $idParser
	 * @param LabelDescriptionDuplicateDetector $termDuplicateDetector
	 *
	 * @throws \InvalidArgumentException
	 */
	function __construct(
		$maxLength,
		array $languages,
		EntityIdParser $idParser,
		LabelDescriptionDuplicateDetector $termDuplicateDetector
	) {
		if ( !is_int( $maxLength ) || $maxLength <= 0 ) {
			throw new \InvalidArgumentException( '$maxLength must be a positive integer.' );
		}

		$this->maxLength = $maxLength;
		$this->languages = $languages;
		$this->idParser = $idParser;
		$this->termDuplicateDetector = $termDuplicateDetector;
	}

	/**
	 * Returns a validator for checking global uniqueness constraints.
	 * This is intended for checking "soft constraints". For hard constraints,
	 * see EntityContent::getOnSaveValidators().
	 *
	 * @param string $entityType
	 *
	 * @return EntityValidator
	 */
	public function getUniquenessValidator( $entityType ) {
		//TODO: Make this configurable. Use a builder. Allow more types to register.
		if ( $entityType === Property::ENTITY_TYPE ) {
			return new LabelUniquenessValidator( $this->termDuplicateDetector );
		} else {
			return new LabelDescriptionUniquenessValidator( $this->termDuplicateDetector );
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
