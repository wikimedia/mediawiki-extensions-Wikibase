<?php

/**
 * Page for testing the Solr search extension for Wikibase
 *
 * @since 0.2
 *
 * @file SpecialSolrSearch.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Denny Vrandecic < vrandecic@gmail.com >
 */
class SpecialSolrSearch extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'SolrSearch' );
	}

	/**
	 * Main method.
	 *
	 * @since 0.2
	 *
	 * @param string|null $subPage
	 *
	 * @return boolean if the page call was successful
	 */
	public function execute( $subPage ) {
		if ( ! parent::execute( $subPage ) ) {
			return false;
		}

		// Setup
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );
		$language = $request->getVal( 'language', isset( $parts[0] ) ? $parts[0] : '' );
		if ( $language === '' ) {
			$language = $this->getLanguage()->getCode();
		}
		$label = $request->getVal( 'search', isset( $parts[1] ) ? $parts[1] : '' );
		if ( $label === '' ) {
			$label = null;
		}

		$this->switchForm( $language, $label );

		//$this->testSolarium();

		// Display the result set
		if ( isset( $language ) && isset( $label ) ) {
			// TODO: should search over aliases as well, not just labels
			$itemContents = \Wikibase\EntityContentFactory::singleton()->getFromLabel( $language, $label, null, \Wikibase\Item::ENTITY_TYPE, true );

			if ( 0 < count( $itemContents ) ) {
				$this->getOutput()->setPageTitle( $this->msg( 'wikibase-disambiguation-title', $label )->escaped() );
				$this->displayDisambiguationPage( $itemContents, $language );
			} else {
				// No results found
				if ( ( Language::isValidBuiltInCode( $language ) && ( Language::fetchLanguageName( $language ) !== "" ) ) ) {
					// No valid language code
					$this->getOutput()->addWikiMsg( 'wikibase-itemdisambiguation-nothing-found' );
					if ( $language === $this->getLanguage()->getCode() ) {
						$this->getOutput()->addWikiMsg( 'wikibase-itemdisambiguation-create', $label );
					}
				} else {
					$this->getOutput()->addWikiMsg( 'wikibase-itemdisambiguation-invalid-langcode' );
				}
			}
		}

		return true;
	}

	/**
	 * Output a form to allow searching for labels
	 *
	 * @since 0.2
	 *
	 * @param string|null $langCode
	 * @param string|null $label
	 */
	protected function switchForm( $langCode, $label ) {
		$this->getOutput()->addModules( 'wikibase.special.itemDisambiguation' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getTitle()->getFullUrl(),
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
				$langCode ? $langCode : '',
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
				$label ? $label : '',
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
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

	/**
	 * Checks for the existence of Solarium and if Solr is running.
	 * Adds the result to the output.
	 */
	protected function testSolarium() {
		$this->getOutput()->addWikiText( 'Testing Solarium' );

		global $wgWBSolarium;
		require_once( $wgWBSolarium );
		Solarium_Autoloader::register();

		// check solarium version available
		$this->getOutput()->addWikiText( 'Solarium library version: ' . Solarium_Version::VERSION );

		// create a client instance
		$client = new Solarium_Client();

		// create a ping query
		$ping = $client->createPing();

		// execute the ping query
		try{
			$client->ping($ping);
			$this->getOutput()->addWikiText( 'Ping query successful' );
		}catch(Solarium_Exception $e){
			$this->getOutput()->addWikiText( 'Ping query failed' );
		}

		$query = $client->createSelect();
		$result = $client->select($query);

		$this->getOutput()->addWikiText( 'Number of results found: ' . $result->getNumFound() );

		foreach ( $result as $document ) {
			// the documents are also iterable, to get all fields
			foreach( $document as $field => $value ) {
				// this converts multivalue fields to a comma-separated string
				if (is_array( $value ) ) $value = implode(', ', $value);

				$this->getOutput()->addWikiText( '* ' . $field . ': ' . $value );

			}
		}

	}

}
