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
	 *
	 * @return boolean
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$items = array(); // TODO

		if ( empty( $items ) ) {
			// TODO
		}
		elseif ( count( $items ) !== 1 ) {
			$this->displayDisambiguationPage( $items );
		}
		else {
			$this->displayItem( $items[0] );
		}
	}

	protected function displayDisambiguationPage( array /* of WikibaseItem */ $items ) {
		// TODO
	}

}
