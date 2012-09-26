<?php

/**
 * Enables accessing items by providing the identifier of a site and the title
 * of the corresponding page on that site.
 *
 * @since 0.1
 *
 * @file
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
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );
		$siteId = $request->getVal( 'site', isset( $parts[0] ) ? $parts[0] : '' );
		$page = $request->getVal( 'page', isset( $parts[1] ) ? $parts[1] : '' );

		$pageTitle = '';
		$itemContent = null;

		if ( !empty( $page ) ) {
			$pageTitle = \Title::newFromText( $page )->getText();

			// Create an item view
			if ( isset( $siteId ) && isset( $pageTitle ) ) {
				$itemContent = \Wikibase\ItemHandler::singleton()->getFromSiteLink( $siteId, $pageTitle );
				if ( $itemContent !== null ) {
					$itemUrl = $itemContent->getTitle()->getFullUrl();
					$this->getOutput()->redirect( $itemUrl );
				}
			}
		}

		// If there is no item content post the switch form
		if ( $itemContent === null ) {
			$this->switchForm( $siteId, $pageTitle );
		}
	}

	/**
	 * Output a form to allow searching for a page
	 *
	 * @since 0.1
	 *
	 * @param string|null $siteId
	 * @param string|null $page
	 */
	protected function switchForm( $siteId, $page ) {

		$group = \Wikibase\Settings::get( 'siteLinkGroup' );
		$sites = \Sites::singleton()->getSiteGroup( $group );

		$siteExists = $sites->hasSite( $siteId );

		$sites = \Wikibase\ItemView::getSiteDetails();
		$this->getOutput()->addJsConfigVars( 'wbSiteDetails', $sites );
		$this->getOutput()->addModules( 'wikibase.special.itemByTitle' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getTitle()->getFullUrl(),
					'name' => 'itembytitle',
					'id' => 'wb-itembytitle-form1'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-itembytitle-lookup-fieldset' )->text()
			)
			. Html::element(
				'label',
				array( 'for' => 'wb-itembytitle-sitename' ),
				$this->msg( 'wikibase-itembytitle-lookup-site' )->text()
			)
			. Html::input(
				'site',
				$siteId,
				'text',
				array(
					'id' => 'wb-itembytitle-sitename',
					'size' => 12
				)
			)
			. ' '
			. Html::element(
				'label',
				array( 'for' => 'pagename' ),
				$this->msg( 'wikibase-itembytitle-lookup-page' )->text()
			)
			. Html::input(
				'page',
				$page,
				'text',
				array(
					'id' => 'pagename',
					'size' => 36,
					'class' => 'wb-input-text'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-itembytitle-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-itembytitle-submit',
					'class' => 'wb-input-button'
				)
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
		if ( $siteExists && isset( $page ) ) {
			$this->getOutput()->addHTML(
				Html::openElement( 'div' )
				. $this->msg( 'wikibase-itembytitle-create' )
					->params(
						wfUrlencode( isset( $siteId ) ? $siteId : '' ),
						wfUrlencode( isset( $page) ? $page : '' ),
						isset( $page)  ? htmlspecialchars( $page ) : ''
					)
					->parse()
				. Html::closeElement( 'div' )
			);
		}

	}

}
