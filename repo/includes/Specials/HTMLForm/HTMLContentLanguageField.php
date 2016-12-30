<?php

namespace Wikibase\Repo\Specials\HTMLForm;

use InvalidArgumentException;
use HTMLComboboxField;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class representing generic content language selector field
 *
 * @license GPL-2.0+
 */
class HTMLContentLanguageField extends HTMLComboboxField {


	/**
	 * Can be used without label - has some predefined value. <br>
	 * Doesn't accept any of options parameters. <br>
	 *
	 * @inheritdoc
	 *
	 * @see \HTMLForm There is detailed description of $info array
	 */
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
				"Cannot set options for content language field. It already has it's own options"
			);
		}

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$contentLanguages = $wikibaseRepo->getTermsLanguages();
		$params['options'] = $this->constructOptions(
			$contentLanguages->getLanguages(),
			$wikibaseRepo->getLanguageNameLookup()
		);

		parent::__construct( array_merge( $defaultParameters, $params ) );
	}

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
		if ( array_search( $value, $options ) === false ) {
			return $this->msg( 'wikibase-content-language-edit-not-recognized-language' );
		}

		return parent::validate( $value, $alldata );
	}

}
