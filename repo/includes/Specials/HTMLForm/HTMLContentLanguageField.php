<?php

namespace Wikibase\Repo\Specials\HTMLForm;

use InvalidArgumentException;
use MediaWiki\Context\IContextSource;
use MediaWiki\HTMLForm\Field\HTMLComboboxField;
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

		$parent = $params['parent'] ?? null;
		if ( !( $parent instanceof IContextSource ) ) {
			throw new InvalidArgumentException( 'parent option must be an IContextSource' );
		}
		$languageCode = $parent->getLanguage()->getCode();
		$params['default'] ??= $languageCode;

		if ( isset( $params['options'] )
			 || isset( $params['options-message'] )
			 || isset( $params['options-messages'] )
		) {
			throw new InvalidArgumentException(
				"Cannot set options for content language field. It already has its own options"
			);
		}

		$contentLanguages = WikibaseRepo::getTermsLanguages();
		$params['options'] = $this->constructOptions(
			$contentLanguages->getLanguages(),
			WikibaseRepo::getLanguageNameLookupFactory()->getForLanguageCode( $languageCode )
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
			$languageName = $lookup->getNameForTerms( $code );
			$languageOptions["$languageName ($code)"] = $code;
		}

		return $languageOptions;
	}

	/** @inheritDoc */
	public function validate( $value, $alldata ) {
		$options = $this->getOptions();
		if ( !in_array( $value, $options, true ) ) {
			return $this->msg( 'wikibase-content-language-edit-not-recognized-language' );
		}

		return parent::validate( $value, $alldata );
	}

}
