<?php

/**
 * Enables accessing items by providing the label of the item and the language of the label.
 * If there are multiple items with this label, a disambiguation page is shown.
 *
 * @since 0.1
 *
 * @file SpecialItemDisambiguation.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SpecialItemDisambiguation extends SpecialItemResolver {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'ItemDisambiguation' );
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

		$this->switchForm( $language, $label );
		
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

		$sites = \Wikibase\ItemView::getSiteDetails();

		// The next two lines are here for the site ID autocompletion
		$this->getOutput()->addJsConfigVars( 'wbSiteDetails', $sites ); // TODO: This should really be in a Resource loader module and not here.
		$this->getOutput()->addModules( 'wikibase.special.itemDisambiguation' );

		$this->getOutput()->addHTML(
			Html::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript, 'name' => 'itemdisambiguation', 'id' => 'wb-itemdisambiguation-form1' ) )
			. Html::hidden( 'title',  $this->getTitle()->getPrefixedText() )
			. Xml::fieldset( $this->msg( 'wikibase-itemdisambiguation-lookup-fieldset' )->text() )
			. Xml::inputLabel( $this->msg( 'wikibase-itemdisambiguation-lookup-language' )->text(), 'language', 'wb-itemdisambiguation-languagename', 12, $langCode ? $langCode : '' )
			. Xml::inputLabel( $this->msg( 'wikibase-itemdisambiguation-lookup-label' )->text(), 'label', 'labelname', 36, $label ? $label : '' )
			. Xml::submitButton( $this->msg( 'wikibase-itemdisambiguation-submit' )->text() )
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

}
