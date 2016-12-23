<?php

namespace Wikibase\Repo\Specials\HTMLForm;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use HTMLComboboxField;
use Wikibase\Repo\WikibaseRepo;

class HTMLContentLanguageField extends HTMLComboboxField {

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @var \Wikibase\Lib\LanguageNameLookup
	 */
	private $languageNameLookup;

	public function __construct( array $params ) {
		$defaultParameters = [
			'label-message' => 'wikibase-content-language-edit-label',
		];

		if ( isset( $params['parent'] ) && $params['parent'] instanceof \IContextSource ) {
			/** @var \IContextSource $form */
			$form = $params['parent'];
			$params['default'] = $form->getLanguage()->getCode();
		}

		if ( isset( $params['options'] )
			 || isset( $params['options-message'] )
			 || isset( $params['options-messages'] )
		) {
			throw new InvalidArgumentException(
				"Can not set options for content language field. It already has it's own options"
			);
		}
		$params['options'] = $this->getLanguageOptions();

		parent::__construct( array_merge( $defaultParameters, $params ) );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->languageCodes = $wikibaseRepo->getTermsLanguages()->getLanguages();
		$this->languageNameLookup = $wikibaseRepo->getLanguageNameLookup();
	}

	/**
	 * Get options for language selector
	 *
	 * @return string[]
	 */
	private function getLanguageOptions() {
		$languageOptions = [];
		foreach ( $this->languageCodes as $code ) {
			$languageName = $this->languageNameLookup->getName( $code );
			$languageOptions["$languageName ($code)"] = $code;
		}

		return $languageOptions;
	}

	public function validate( $value, $alldata ) {
		$options = $this->getOptions();
		if ( array_search( $value, $options ) === false ) {
			return $this->msg( 'wikibase-newitem-not-recognized-language' );
		}

		return parent::validate( $value, $alldata );
	}

}
