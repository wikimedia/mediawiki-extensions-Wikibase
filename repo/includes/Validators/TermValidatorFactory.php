<?php

namespace Wikibase\Validators;

use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\SettingsArray;


/**
 * Defines validation for terms (like the maximum length of labels, etc).
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
	 * @param $maxLength
	 * @param string[] $languages
	 * @param EntityIdParser $idParser
	 *
	 * @throws \InvalidArgumentException
	 */
	function __construct( $maxLength, array $languages, EntityIdParser $idParser ) {
		if ( !is_int( $maxLength ) || $maxLength <= 0 ) {
			throw new \InvalidArgumentException( '$maxLength must be a positive integer.' );
		}

		$this->maxLength = $maxLength;
		$this->languages = $languages;
		$this->idParser = $idParser;
	}

	/**
	 * @param string $entityType
	 *
	 * @return ValueValidator
	 */
	public function getLabelValidator( $entityType ) {
		$validators = $this->getCommonTermValidators();

		//TODO: Make this configurable. Use a builder.
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
