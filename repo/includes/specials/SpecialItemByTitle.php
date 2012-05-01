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

		if ( $this->subPage === '' ) {
			// TODO
		}

		$parts = explode( '/', $this->subPage, 2 );

		if ( count( $parts ) != 2 ) {
			// TODO
		}

		$item = WikibaseItem::getFromSiteLink( $parts[0], $parts[1] );

		if ( is_null( $item ) ) {
			// TODO
		}
		else {
			$this->displayItem( $item );
		}
	}

}
