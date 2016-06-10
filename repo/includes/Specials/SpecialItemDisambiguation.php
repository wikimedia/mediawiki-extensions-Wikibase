<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Html;
use WebRequest;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndexEntry;

/**
 * Enables accessing items by providing the label of the item and the language of the label.
 * A result page is shown, disambiguating between multiple results if necessary.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
class SpecialItemDisambiguation extends SpecialWikibasePage {

	/**
	 * @var ItemDisambiguation DO NOT ACCESS DIRECTLY use this->getItemDisambiguation
	 */
	private $itemDisambiguation;

	/**
	 * @var TermIndexSearchInteractor|null DO NOT ACCESS DIRECTLY use this->getSearchInteractor
	 */
	private $searchInteractor = null;

	/**
	 * @var ContentLanguages
	 */
	private $contentLanguages;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var int
	 */
	private $limit;

	/**
	 * @see SpecialWikibasePage::__construct
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'ItemDisambiguation', '', true );

		// @todo inject these
		$this->contentLanguages = new MediaWikiContentLanguages();
		$this->languageNameLookup = new LanguageNameLookup();

		// @todo make this configurable
		$this->limit = 100;
	}

	/**
	 * Set service objects to use. Unit tests may call this to substitute mock
	 * services.
	 *
	 * @param ItemDisambiguation $itemDisambiguation
	 * @param TermIndexSearchInteractor $searchInteractor
	 * @param ContentLanguages $contentLanguages
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function initServices(
		ItemDisambiguation $itemDisambiguation,
		TermIndexSearchInteractor $searchInteractor,
		ContentLanguages $contentLanguages,
		LanguageNameLookup $languageNameLookup
	) {
		$this->itemDisambiguation = $itemDisambiguation;
		$this->searchInteractor = $searchInteractor;
		$this->contentLanguages = $contentLanguages;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @param string $displayLanguageCode Only used if the service does not already exist
	 *
	 * @return TermIndexSearchInteractor
	 */
	private function getSearchInteractor( $displayLanguageCode ) {
		if ( $this->searchInteractor === null ) {
			$this->searchInteractor = WikibaseRepo::getDefaultInstance()->newTermSearchInteractor(
				$displayLanguageCode
			);
		}
		return $this->searchInteractor;
	}

	/**
	 * @return ItemDisambiguation
	 */
	private function getItemDisambiguation() {
		global $wgLang;

		if ( $this->itemDisambiguation === null ) {
			$this->itemDisambiguation = new ItemDisambiguation(
				WikibaseRepo::getDefaultInstance()->getEntityTitleLookup(),
				new LanguageNameLookup( $wgLang->getCode() ),
				$this->getLanguage()->getCode()
			);
		}

		return $this->itemDisambiguation;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.1
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$request = $this->getRequest();
		$subPageParts = $subPage === '' ? array() : explode( '/', $subPage, 2 );

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
		$searchInteractor = $this->getSearchInteractor( $this->getLanguage()->getCode() );

		return $searchInteractor->searchForEntities(
			$label,
			$languageCode,
			'item',
			array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ),
			$this->getSearchOptions()
		);
	}

	/**
	 * @return TermSearchOptions
	 */
	private function getSearchOptions() {
		$searchOptions = new TermSearchOptions();

		$searchOptions->setLimit( $this->limit );
		$searchOptions->setIsCaseSensitive( false );
		$searchOptions->setIsPrefixSearch( false );
		$searchOptions->setUseLanguageFallback( true );

		return $searchOptions;
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
				$searchLink->getFullURL( array( 'search' => $label ) )
			);
			$createLink = $this->getTitleFor( 'NewItem' );
			$this->getOutput()->addWikiMsg(
				'wikibase-itemdisambiguation-create',
				$createLink->getFullURL( array( 'label' => $label ) )
			);
		}
	}

	/**
	 * Display disambiguation page.
	 *
	 * @param TermSearchResult[] $searchResults
	 */
	private function displayDisambiguationPage( array $searchResults ) {
		$itemDisambiguation = $this->getItemDisambiguation();
		$html = $itemDisambiguation->getHTML( $searchResults );
		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Return options for the language input field.
	 *
	 * @return array
	 */
	private function getLanguageOptions() {
		$options = array();
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
		$formDescriptor = array(
			'language' => array(
				'name' => 'language',
				'default' => $languageCode ?: '',
				'type' => 'combobox',
				'options' => $this->getLanguageOptions(),
				'id' => 'wb-itemdisambiguation-languagename',
				'size' => 12,
				'cssclass' => 'wb-language-suggester',
				'label-message' => 'wikibase-itemdisambiguation-lookup-language'
			),
			'label' => array(
				'name' => 'label',
				'default' => $label ?: '',
				'type' => 'text',
				'id' => 'labelname',
				'size' => 36,
				'autofocus',
				'label-message' => 'wikibase-itemdisambiguation-lookup-label'
			),
			'submit' => array(
				'name' => '',
				'default' => $this->msg( 'wikibase-itemdisambiguation-submit' )->text(),
				'type' => 'submit',
				'id' => 'wb-itembytitle-submit',
			)
		);

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setId( 'wb-itemdisambiguation-form1' )
			->setMethod( 'get' )
			->setFooterText( Html::element(
				'p',
				array(),
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
