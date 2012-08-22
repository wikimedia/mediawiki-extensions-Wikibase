<?php

/**
 * Enables accessing items by providing the label of the item and the language of the label.
 * If there are multiple items with this label, a disambiguation page is shown.
 *
 * @since 0.1
 *
 * @file SpecialItemByLabel.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SpecialItemByLabel extends SpecialItemResolver {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'ItemByLabel' );
	}

	/**
	 * Main method.
	 *
	 * @since 0.1
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		// Setup
		$request = $this->getRequest();
		$parts = ( $this->subPage === '' ) ? array() : explode( '/', $this->subPage, 2 );
		$language = $request->getVal( 'language', isset( $parts[0] ) ? $parts[0] : '' );
		if ( $language === '' ) {
			$language = null;
		}
		$label = $request->getVal( 'label', isset( $parts[1] ) ? $parts[1] : '' );
		if ( $label === '' ) {
			$label = null;
		}
		$itemContents = array();

		// Create an item view
		if ( isset( $language ) && isset( $label ) ) {
			$itemContents = \Wikibase\ItemHandler::singleton()->getFromLabel( $language, $label );
			//$itemContents = call_user_func_array(
			//	array( \Wikibase\ItemHandler::singleton(), 'getFromLabel' ),
			//	array( $language, $label, $description )
			//);
			if ( count( $itemContents ) === 1 && $this->getRequest()->wasPosted() ) {
				$this->displayItem( $itemContents[0] );
			}
			if ( 0 < count( $itemContents ) ) {
				$this->getOutput()->setPageTitle( $this->msg( 'wikibase-disambiguation-title', $label )->escaped() );
				$this->displayDisambiguationPage( $itemContents, $language );
			}
		}

		// If there is no item content post the switch form
		if ( $itemContents === array() ) {
			$this->switchForm( $language, $label );
		}
	}

	/**
	 * Display disambiguation page.
	 *
	 * @since 0.1
	 *
	 * @param array $items
	 * @param string $langCode
	 */
	protected function displayDisambiguationPage( array /* of ItemContent */ $items, $langCode ) {
		$disambiguationList = new Wikibase\ItemDisambiguation( $items, $langCode, $this->getContext() );
		$disambiguationList->display();
	}

	/**
	 * Output a form to allow searching for a page
	 *
	 * @since 0.1
	 *
	 * @param string|null $site
	 * @param string|null $page
	 */
	protected function switchForm( $langCode, $label ) {
		global $wgScript;

		// Misuse language name to figure out if the code is defined
		$languageName = \Wikibase\Utils::fetchLanguageName( $langCode );

		if ( isset( $langCode ) || isset( $label ) ) {
			$this->getOutput()->addHTML(
				Html::openElement( 'div' )
				. $this->msg( $languageName !== $langCode ? 'wikibase-itembylabel-nothing-found' : 'wikibase-itembylabel-invalid-langcode' )
					->params( $langCode, $label )
					->parse()
				. Html::closeElement( 'div' )
			);
		}
		$this->getOutput()->addHTML(
			Html::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript, 'name' => 'itembylabel', 'id' => 'mw-itembylabel-form1' ) )
			. Html::hidden( 'title',  $this->getTitle()->getPrefixedText() )
			. Xml::fieldset( $this->msg( 'wikibase-itembylabel-lookup-fieldset' )->text() )
			. Xml::inputLabel( $this->msg( 'wikibase-itembylabel-lookup-language' )->text(), 'language', 'languagename', 12, $langCode ? $langCode : '' )
			. Xml::inputLabel( $this->msg( 'wikibase-itembylabel-lookup-label' )->text(), 'label', 'labelname', 36, $label ? $label : '' )
			. Xml::submitButton( $this->msg( 'wikibase-itembylabel-submit' )->text() )
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
		$this->getOutput()->addHTML(
			Html::openElement( 'div' )
			. $this->msg( 'wikibase-itembylabel-description' )->text()
			. Html::closeElement( 'div' )
		);
		if ( isset( $langCode ) && isset( $label ) ) {
			$this->getOutput()->addHTML(
				Html::openElement( 'div' )
				. $this->msg( 'wikibase-itembylabel-create' )
					->params(
						wfUrlencode( $langCode ? $langCode : '' ),
						wfUrlencode( $label ? $label : '' ),
						$label ? $label : ''
					)
					->parse()
				. Html::closeElement( 'div' )
			);
		}
	}

}
