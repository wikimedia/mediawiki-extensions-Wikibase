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

		if ( $this->subPage === '' ) {
			// TODO: display a message that the user needs to provide language+label and possibly some fancy input UI
		}

		// TODO: document that the label cannot have slashes (or pick other separator)
		$parts = explode( '/', $this->subPage, 3 );

		if ( count( $parts ) == 1 ) {
			// TODO: display a message that the user needs to provide  the label and possibly some fancy input UI
		}
		else {
			$items = call_user_func_array( 'Wikibase\Item::getFromLabel', $parts );

			if ( $items === array() ) {
				// TODO: display that there are no matching items and possibly some fancy input UI
			}
			elseif ( count( $items ) !== 1 ) {
				$this->getOutput()->setPageTitle( $this->msg( 'wikibase-disambiguation-title', $parts[1] )->escaped() );
				$this->displayDisambiguationPage( $items, $parts[0] );
			}
			else {
				$this->displayItem( $items[0] );
			}
		}
	}

	protected function displayDisambiguationPage( array /* of WikibaseItem */ $items, $langCode ) {
		$disambiguationList = new Wikibase\ItemDisambiguation( $items, $langCode, $this->getContext() );
		$disambiguationList->display();
	}

}
