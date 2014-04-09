<?php

namespace Wikibase\Lib;

use ValueValidators\ValueValidator;
use Wikibase\SettingsArray;
use Wikibase\Validators\CompositeValidator;
use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\TypeValidator;
use Wikibase\Validators\InArrayValidator;


/**
 * Defines validation for terms (like the maximum length of labels, etc).
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseTermValidatorBuilders {

	/**
	 * @var SettingsArray
	 */
	protected $settings;

	/**
	 * @var string[]
	 */
	protected $languages;

	/**
	 * @param SettingsArray $settings
	 * @param string[] $languages
	 */
	function __construct( SettingsArray $settings, $languages ) {
		$this->settings = $settings;
		$this->languages = $languages;
	}

	/**
	 * @return ValueValidator
	 */
	public function buildLabelValidator() {
		$constraints = $this->settings->getSetting( 'multilang-limits' );
		$maxLength = $constraints['length'];

		return $this->buildTermValidator( $maxLength );
	}

	/**
	 * @return ValueValidator
	 */
	public function buildDescriptionValidator() {
		$constraints = $this->settings->getSetting( 'multilang-limits' );
		$maxLength = $constraints['length'];

		return $this->buildTermValidator( $maxLength );
	}

	/**
	 * @return ValueValidator
	 */
	public function buildAliasValidator() {
		$constraints = $this->settings->getSetting( 'multilang-limits' );
		$maxLength = $constraints['length'];

		return $this->buildTermValidator( $maxLength );
	}

	/**
	 * @param int $maxLength
	 *
	 * @return ValueValidator
	 */
	protected function buildTermValidator( $maxLength ) {
		$validators = array();
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 1, $maxLength, 'mb_strlen' );
		$validators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.

		$validator = new CompositeValidator( $validators, true ); //Note: each validator is fatal
		return $validator;
	}

	/**
	 * @return ValueValidator
	 */
	public function buildLanguageValidator() {
		$validators = array();
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new InArrayValidator( $this->languages, 'not-a-language' );

		$validator = new CompositeValidator( $validators, true ); //Note: each validator is fatal
		return $validator;
	}

}
