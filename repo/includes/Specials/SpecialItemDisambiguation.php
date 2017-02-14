<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Html;
use WebRequest;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\TermIndexEntry;

/**
 * Enables accessing items by providing the label of the item and the language of the label.
 * A result page is shown, disambiguating between multiple results if necessary.
 *
 * @license GPL-2.0+
 */
class SpecialItemDisambiguation extends SpecialWikibasePage {

	/**
	 * @var ContentLanguages
	 */
	private $contentLanguages;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var ItemDisambiguation
	 */
	private $itemDisambiguation;

	/**
	 * @var ConfigurableTermSearchInteractor
	 */
	private $searchInteractor = null;

	/**
	 * @var int
	 */
	private $limit;

	/**
	 * @param ContentLanguages $contentLanguages
	 * @param LanguageNameLookup $languageNameLookup
	 * @param ItemDisambiguation $itemDisambiguation
	 * @param ConfigurableTermSearchInteractor $searchInteractor
	 * @param int $limit
	 */
	public function __construct(
		ContentLanguages $contentLanguages,
		LanguageNameLookup $languageNameLookup,
		ItemDisambiguation $itemDisambiguation,
		ConfigurableTermSearchInteractor $searchInteractor,
		$limit = 100
	) {
		parent::__construct( 'ItemDisambiguation' );

		$this->contentLanguages = $contentLanguages;
		$this->languageNameLookup = $languageNameLookup;
		$this->itemDisambiguation = $itemDisambiguation;
		$this->searchInteractor = $searchInteractor;
		$this->limit = $limit;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$request = $this->getRequest();
		$subPageParts = $subPage === '' ? [] : explode( '/', $subPage, 2 );

		$languageCode = $this->extractLanguageCode( $request, $subPageParts );
		$label = $this->extractLabel( $request, $subPageParts );

		$this->switchForm( $languageCode, $label );

		// Display the result set
		if ( isset( $languageCode ) && isset( $label ) && $label !== '' ) {
			// @todo We could have a LanguageCodeValidator or something and handle this
			// in the search interactor or some place else.
			if ( !$this->contentLanguages->hasLanguage( $languageCode ) ) {
				$this->showErrorHTML(
					$this->msg( 'wikibase-itemdisambiguation-invalid-langcode' )->escaped()
				);
			} else {
				$searchResults = $this->getSearchResults( $label, $languageCode );

				if ( !empty( $searchResults ) ) {
					$this->displaySearchResults( $searchResults, $label );
				} else {
					$this->showNothingFound( $languageCode, $label );
				}
			}
		}
	}

	/**
	 * @param WebRequest $request
	 * @param array $subPageParts
	 *
	 * @return string
	 */
	private function extractLanguageCode( WebRequest $request, array $subPageParts ) {
		$languageCode = $request->getVal(
			'language',
			isset( $subPageParts[0] ) ? $subPageParts[0] : ''
		);

		if ( $languageCode === '' ) {
			$languageCode = $this->getLanguage()->getCode();
		}

		return $languageCode;
	}

	/**
	 * @param WebRequest $request
	 * @param array $subPageParts
	 *
	 * @return string
	 */
	private function extractLabel( WebRequest $request, array $subPageParts ) {
		if ( $request->getCheck( 'label' ) ) {
			return $request->getText( 'label' );
		}

		return isset( $subPageParts[1] ) ? str_replace( '_', ' ', $subPageParts[1] ) : '';
	}

	/**
	 * @param TermSearchResult[] $searchResults
	 * @param string $label
	 */
	private function displaySearchResults( array $searchResults, $label ) {
		$this->getOutput()->setPageTitle(
			$this->msg( 'wikibase-disambiguation-title', $label )->escaped()
		);

		$this->displayDisambiguationPage( $searchResults );
	}

	/**
	 * @param string $label
	 * @param string $languageCode
	 *
	 * @return TermSearchResult[]
	 */
	private function getSearchResults( $label, $languageCode ) {
		$searchOptions = new TermSearchOptions();
		$searchOptions->setLimit( $this->limit );
		$searchOptions->setIsCaseSensitive( false );
		$searchOptions->setIsPrefixSearch( false );
		$searchOptions->setUseLanguageFallback( true );

		$this->searchInteractor->setTermSearchOptions( $searchOptions );

		return $this->searchInteractor->searchForEntities(
			$label,
			$languageCode,
			'item',
			[ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ]
		);
	}

	/**
	 * Shows information, assuming no results were found.
	 *
	 * @param string $languageCode
	 * @param string $label
	 */
	private function showNothingFound( $languageCode, $label ) {
		$this->getOutput()->addWikiMsg( 'wikibase-itemdisambiguation-nothing-found' );

		if ( $languageCode === $this->getLanguage()->getCode() ) {
			$searchLink = $this->getTitleFor( 'Search' );
			$this->getOutput()->addWikiMsg(
				'wikibase-itemdisambiguation-search',
				$searchLink->getFullURL( [ 'search' => $label ] )
			);
			$createLink = $this->getTitleFor( 'NewItem' );
			$this->getOutput()->addWikiMsg(
				'wikibase-itemdisambiguation-create',
				$createLink->getFullURL( [ 'label' => $label ] )
			);
		}
	}

	/**
	 * Display disambiguation page.
	 *
	 * @param TermSearchResult[] $searchResults
	 */
	private function displayDisambiguationPage( array $searchResults ) {
		$html = $this->itemDisambiguation->getHTML( $searchResults );
		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Return options for the language input field.
	 *
	 * @return array
	 */
	private function getLanguageOptions() {
		$options = [];
		foreach ( $this->contentLanguages->getLanguages() as $languageCode ) {
			$languageName = $this->languageNameLookup->getName( $languageCode );
			$options["$languageName ($languageCode)"] = $languageCode;
		}
		return $options;
	}

	/**
	 * Output a form to allow searching for labels
	 *
	 * @param string|null $languageCode
	 * @param string|null $label
	 */
	private function switchForm( $languageCode, $label ) {
		$formDescriptor = [
			'language' => [
				'name' => 'language',
				'default' => $languageCode ?: '',
				'type' => 'combobox',
				'options' => $this->getLanguageOptions(),
				'id' => 'wb-itemdisambiguation-languagename',
				'size' => 12,
				'cssclass' => 'wb-language-suggester',
				'label-message' => 'wikibase-itemdisambiguation-lookup-language'
			],
			'label' => [
				'name' => 'label',
				'default' => $label ?: '',
				'type' => 'text',
				'id' => 'labelname',
				'size' => 36,
				'autofocus',
				'label-message' => 'wikibase-itemdisambiguation-lookup-label'
			],
			'submit' => [
				'name' => '',
				'default' => $this->msg( 'wikibase-itemdisambiguation-submit' )->text(),
				'type' => 'submit',
				'id' => 'wb-itembytitle-submit',
			]
		];

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setId( 'wb-itemdisambiguation-form1' )
			->setMethod( 'get' )
			->setFooterText( Html::element(
				'p',
				[],
				$this->msg( 'wikibase-itemdisambiguation-form-hints' )->numParams(
					$this->limit
				)->text()
			) )
			->setWrapperLegendMsg( 'wikibase-itemdisambiguation-lookup-fieldset' )
			->suppressDefaultSubmit()
			->setSubmitCallback( function () {// no-op
			} )->show();
	}

}
