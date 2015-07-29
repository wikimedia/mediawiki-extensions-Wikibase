<?php

namespace Wikibase\Repo\Specials;

use Html;
use Language;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;
use Wikibase\Lib\Store\LanguageLabelDescriptionLookup;
use Wikibase\Repo\Interactors\TermIndexSearchInteractor;
use Wikibase\Repo\Interactors\TermSearchResult;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndexEntry;

/**
 * Enables accessing items by providing the label of the item and the language of the label.
 * A result page is shown, disambiguating between multiple results if necessary.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
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
		//@todo: make this configurable
		$this->limit = 100;
	}

	/**
	 * Set service objects to use. Unit tests may call this to substitute mock
	 * services.
	 *
	 * @param ItemDisambiguation $itemDisambiguation
	 * @param TermIndexSearchInteractor|null $searchInteractor
	 */
	public function initServices(
		ItemDisambiguation $itemDisambiguation,
		TermIndexSearchInteractor $searchInteractor
	) {
		$this->itemDisambiguation = $itemDisambiguation;
		$this->searchInteractor = $searchInteractor;
	}

	/**
	 * @param string $displayLanguageCode Only used if the service does not already exist
	 *
	 * @return TermIndexSearchInteractor
	 */
	private function getSearchInteractor( $displayLanguageCode ) {
		if ( $this->searchInteractor === null ) {
			$interactor = WikibaseRepo::getDefaultInstance()->newTermSearchInteractor( $displayLanguageCode );
			$this->searchInteractor = $interactor;
		}
		return $this->searchInteractor;
	}

	/**
	 * @return ItemDisambiguation
	 */
	private function getItemDisambiguation() {
		if ( $this->itemDisambiguation === null ) {
			$languageNameLookup = new LanguageNameLookup();
			$this->itemDisambiguation = new ItemDisambiguation(
				WikibaseRepo::getDefaultInstance()->getEntityTitleLookup(),
				$languageNameLookup,
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

		// Setup
		$request = $this->getRequest();
		$parts = $subPage === '' ? array() : explode( '/', $subPage, 2 );
		$languageCode = $request->getVal( 'language', isset( $parts[0] ) ? $parts[0] : '' );
		if ( $languageCode === '' ) {
			$languageCode = $this->getLanguage()->getCode();
		}

		if ( $request->getCheck( 'label' ) ) {
			$label = $request->getText( 'label' );
		} else {
			$label = isset( $parts[1] ) ? str_replace( '_', ' ', $parts[1] ) : '';
		}

		$this->switchForm( $languageCode, $label );

		// Display the result set
		if ( isset( $languageCode ) && isset( $label ) && $label !== '' ) {
			$searchInteractor = $this->getSearchInteractor( $this->getLanguage()->getCode() );
			$searchInteractor->setLimit( $this->limit );
			$searchInteractor->setIsCaseSensitive( false );
			$searchInteractor->setIsPrefixSearch( false );
			$searchInteractor->setUseLanguageFallback( true );

			$searchResults = $searchInteractor->searchForEntities(
				$label,
				$languageCode,
				'item',
				array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS )
			);

			if ( 0 < count( $searchResults ) ) {
				$this->getOutput()->setPageTitle( $this->msg( 'wikibase-disambiguation-title', $label )->escaped() );
				$this->displayDisambiguationPage( $searchResults );
			} else {
				$this->showNothingFound( $languageCode, $label );
			}
		}
	}

	/**
	 * Shows information, assuming no results were found.
	 *
	 * @param string $languageCode
	 * @param string $label
	 */
	private function showNothingFound( $languageCode, $label ) {
		// No results found
		if ( ( Language::isValidBuiltInCode( $languageCode ) && ( Language::fetchLanguageName( $languageCode ) !== "" ) ) ) {
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
		} else {
			// No valid language code
			$this->getOutput()->addWikiMsg( 'wikibase-itemdisambiguation-invalid-langcode' );
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
	 * Output a form to allow searching for labels
	 *
	 * @param string|null $languageCode
	 * @param string|null $label
	 */
	private function switchForm( $languageCode, $label ) {
		$this->getOutput()->addModules( 'wikibase.special.itemDisambiguation' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getPageTitle()->getFullUrl(),
					'name' => 'itemdisambiguation',
					'id' => 'wb-itemdisambiguation-form1'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-itemdisambiguation-lookup-fieldset' )->text()
			)
			. Html::element(
				'label',
				array( 'for' => 'wb-itemdisambiguation-languagename' ),
				$this->msg( 'wikibase-itemdisambiguation-lookup-language' )->text()
			)
			. Html::input(
				'language',
				$languageCode ?: '',
				'text',
				array(
					'id' => 'wb-itemdisambiguation-languagename',
					'size' => 12,
					'class' => 'wb-input-text'
				)
			)
			. ' '
			. Html::element(
				'label',
				array( 'for' => 'labelname' ),
				$this->msg( 'wikibase-itemdisambiguation-lookup-label' )->text()
			)
			. Html::input(
				'label',
				$label ?: '',
				'text',
				array(
					'id' => 'labelname',
					'size' => 36,
					'class' => 'wb-input-text',
					'autofocus'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-itemdisambiguation-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-itembytitle-submit',
					'class' => 'wb-input-button'
				)
			)
			. Html::element(
				'p',
				array(),
				$this->msg( 'wikibase-itemdisambiguation-form-hints' )->numParams( $this->limit )->text()
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

}
