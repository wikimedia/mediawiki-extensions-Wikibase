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

		$item = false; // TODO

		if ( $item === false ) {
			// TODO
		}
		else {
			$this->displayItem( $item );
		}
	}

}
