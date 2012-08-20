<?php

/**
 * Enables accessing items by providing the identifier of a site and the title
 * of the corresponding page on that site.
 *
 * @since 0.1
 *
 * @file SpecialItemByTitle.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SpecialItemByTitle extends SpecialItemResolver {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'ItemByTitle' );
	}

	/**
	 * Main method.
	 *
	 * @since 0.1
	 *
	 * @param string|null $subPage
	 *
	 * @return boolean
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		// Setup
		$request = $this->getRequest();
		$parts = ( $this->subPage === '' ) ? array() : explode( '/', $this->subPage, 2 );
		$siteId = $request->getVal( 'site', isset( $parts[0] ) ? $parts[0] : '' );
		$page = $request->getVal( 'page', isset( $parts[1] ) ? $parts[1] : '' );
		$itemContent = null;

		// Create an item view
		if ( isset( $siteId ) && isset( $page ) ) {
			$itemContent = \Wikibase\ItemHandler::singleton()->getFromSiteLink( $siteId, $page );
			if ( $itemContent !== null ) {
				$itemUrl = $itemContent->getTitle()->getFullUrl( $query );
				$this->getOutput()->redirect( $itemUrl );
			}
		}

		// If there is no item cointent post the switch form
		if ( $itemContent === null ) {
			$this->switchForm( $siteId, $page );
		}
	}

	/**
	 * Output a form to allow searching for a page
	 *
	 * @since 0.1
	 *
	 * @param string|null $site
	 * @param string|null $page
	 */
	protected function switchForm( $siteId, $page ) {
		global $wgScript;

		$validSite = \Wikibase\Sites::singleton()->getGroup( SITE_GROUP_WIKIPEDIA )->hasGlobalId( $siteId );

		if ( isset( $siteId ) || isset( $page ) ) {
			$this->getOutput()->addHTML(
				Html::openElement( 'div' )
				. $this->msg( $validSite ? 'wikibase-itembytitle-nothing-found' : 'wikibase-itembytitle-invalid-site' )
					->params( htmlspecialchars( $siteId ), htmlspecialchars( $page ) )
					->parse()
				. Html::closeElement( 'div' )
			);
		}
		$this->getOutput()->addHTML(
			Html::openElement( 'form', array( 'method' => 'post', 'action' => $wgScript, 'name' => 'itembytitle', 'id' => 'mw-itembytitle-form1' ) )
			. Html::hidden( 'title',  $this->getTitle()->getPrefixedText() )
			. Xml::fieldset( $this->msg( 'wikibase-itembytitle-lookup-fieldset' )->text() )
			. Xml::inputLabel( $this->msg( 'wikibase-itembytitle-lookup-site' )->text(), 'site', 'sitename', 12, $siteId ? htmlspecialchars( $siteId ) : '' )
			. Xml::inputLabel( $this->msg( 'wikibase-itembytitle-lookup-page' )->text(), 'page', 'pagename', 36, $page ? htmlspecialchars( $page ) : '' )
			. Xml::submitButton( $this->msg( 'wikibase-itembytitle-submit' )->text() )
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
		$this->getOutput()->addHTML(
			Html::openElement( 'div' )
			. $this->msg( 'wikibase-itembytitle-description' )->text()
			. Html::closeElement( 'div' )
		);
		if ( $validSite && isset( $page ) ) {
			$this->getOutput()->addHTML(
				Html::openElement( 'div' )
				. $this->msg( 'wikibase-itembytitle-create' )
					->params( $siteId ? htmlspecialchars( $siteId ) : '', htmlspecialchars( $page ) )
					->parse()
				. Html::closeElement( 'div' )
			);
		}

	}

}
