<?php

namespace Wikibase\Repo\Specials\HTMLForm;

use HTMLComboboxField;
use InvalidArgumentException;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class representing generic content language selector field
 *
 * @license GPL-2.0-or-later
 */
class HTMLContentLanguageField extends HTMLComboboxField {

	/**
	 * Can be used without label - has some predefined value.
	 *  - Doesn't accept any of options parameters.
	 *
	 * @inheritDoc
	 *
	 * @see \HTMLForm There is detailed description of the allowed $params (named $info there).
	 */
	public function __construct( array $params ) {
		$defaultParameters = [
			'label-message' => 'wikibase-content-language-edit-label',
		];

		if ( isset( $params['parent'] ) && $params['parent'] instanceof \IContextSource ) {
			/** @var \IContextSource $form */
			$form = $params['parent'];
			if ( !isset( $params['default'] ) ) {
				$params['default'] = $form->getLanguage()->getCode();
			}
		}

		if ( isset( $params['options'] )
			 || isset( $params['options-message'] )
			 || isset( $params['options-messages'] )
		) {
			throw new InvalidArgumentException(
				"Cannot set options for content language field. It already has it's own options"
			);
		}

		$contentLanguages = WikibaseRepo::getTermsLanguages();
		$params['options'] = $this->constructOptions(
			$contentLanguages->getLanguages(),
			WikibaseRepo::getLanguageNameLookup()
		);

		parent::__construct( array_merge( $defaultParameters, $params ) );
	}

	/**
	 * @param string[] $languageCodes
	 * @param LanguageNameLookup $lookup
	 *
	 * @return array For details see {@see \HTMLForm} "options" parameter description
	 */
	private function constructOptions( array $languageCodes, LanguageNameLookup $lookup ) {
		$languageOptions = [];
		foreach ( $languageCodes as $code ) {
			$languageName = $lookup->getName( $code );
			$languageOptions["$languageName ($code)"] = $code;
		}

		return $languageOptions;
	}

	public function validate( $value, $alldata ) {
		$options = $this->getOptions();
		if ( !in_array( $value, $options, true ) ) {
			return $this->msg( 'wikibase-content-language-edit-not-recognized-language' );
		}

		return parent::validate( $value, $alldata );
	}

}
