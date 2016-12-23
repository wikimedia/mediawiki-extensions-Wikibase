<?php

namespace Wikibase\Repo\Specials\HTMLForm;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use HTMLComboboxField;
use Language;

class HTMLContentLanguageField extends HTMLComboboxField {

	public function __construct( array $params ) {
		$defaultParameters = [
			'label-message' => 'wikibase-newentity-language',
		];

		if (isset($params['parent']) && $params['parent'] instanceof \IContextSource) {
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
	}

	/**
	 * Get options for language selector
	 *
	 * @return string[]
	 */
	private function getLanguageOptions() {
		$languageOptions = [];
		foreach ( Language::fetchLanguageNames() as $code => $languageName ) {
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
