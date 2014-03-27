<?php

namespace Wikibase\Validators;

use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SiteLinkLookup;
use Wikibase\TermDuplicateDetector;


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
	 * Bit mask representing all uniqueness constraints.
	 */
	const CONSTRAINTS_ALL = 0xFFFF;

	/**
	 * Bit mask representing hard uniqueness constraints, to be enforced rigorously on every save.
	 */
	const CONSTRAINTS_HARD = 0x0001;

	/**
	 * Bit mask representing non-hard uniqueness constraints, to be enforced using a "best effort"
	 * approach.
	 */
	const CONSTRAINTS_NON_HARD = 0xFFFE;

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
	 * @param TermDuplicateDetector $termDuplicateDetector
	 * @param SiteLinkLookup $siteLinkLookup
	 *
	 * @throws \InvalidArgumentException
	 */
	function __construct(
		$maxLength,
		array $languages,
		EntityIdParser $idParser,
		TermDuplicateDetector $termDuplicateDetector,
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
	 * Returns a validator for checking global uniqueness constraints.
	 * This is intended for checking "soft constraints". For hard constraints,
	 * see EntityContent::getOnSaveValidators().
	 *
	 * @todo: this should go into a separate interface.
	 *
	 * @param string $entityType
	 *
	 * @param int $level The desired constraint level, see the CONSTRAINT_XXX constants.
	 *
	 * @return EntityValidator
	 */
	public function getUniquenessValidator( $entityType, $level ) {
		$validators = array();

		//TODO: Make this configurable. Use a builder. Allow more types to register.
		if ( $entityType === Property::ENTITY_TYPE ) {
			if ( $level & self::CONSTRAINTS_HARD ) {
				$validators[] = new LabelUniquenessValidator( $this->termDuplicateDetector );
			}
		}

		if ( $entityType === Item::ENTITY_TYPE ) {
			if ( $level & self::CONSTRAINTS_NON_HARD ) {
				//FIXME: the TitleLookup and the SiteSQLStore will go away I2240d6e0ce
				$validators[] = new SiteLinkUniquenessValidator(
					WikibaseRepo::getDefaultInstance()->getEntityTitleLookup(),
					$this->siteLinkLookup,
					\SiteSQLStore::newInstance()
				);
			}

			if ( $level & self::CONSTRAINTS_NON_HARD ) {
				$validators[] = new LabelDescriptionUniquenessValidator( $this->termDuplicateDetector );
			}
		}

		return new CompositeEntityValidator( $validators );
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
			$validators[] = new NotEntityIdValidator( $this->idParser, array( Property::ENTITY_TYPE ) );
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
		$validators[] = new InArrayValidator( $this->languages, 'not-a-language' );

		$validator = new CompositeValidator( $validators, true ); //Note: each validator is fatal
		return $validator;
	}

}
