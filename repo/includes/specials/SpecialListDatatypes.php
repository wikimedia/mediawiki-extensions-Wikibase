<?php

/**
 * Page for creating new Wikibase items.
 *
 * @since 0.1
 *
 * @file 
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */

use \DataType;

class SpecialListDatatypes extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'ListDatatypes' );

	}
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$this->getOutput()->addHTML( Html::openElement( 'ul' ));
		foreach (\Wikibase\Settings::get( 'dataTypes' ) as $dataTypeId ) {
			$this->getOutput()->addHTML( Html::openElement( 'li' ));
			$this->getOutput()->addHTML( $dataTypeId );
			$this->getOutput()->addHTML( Html::closeElement( 'li' ));
		}
		$this->getOutput()->addHTML( Html::closeElement( 'ul' ));
	}
}
