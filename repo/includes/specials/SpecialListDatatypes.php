<?php

/**
 * Page for listing available datatypes.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */

class SpecialListDatatypes extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'ListDatatypes' );

	}
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$this->getOutput()->addHTML( $this->msg( 'wikibase-listdatatypes-intro' ) );
		$this->getOutput()->addHTML( Html::openElement( 'ul' ));
		foreach (\Wikibase\Settings::get( 'dataTypes' ) as $dataTypeId ) {
			$this->getOutput()->addHTML( Html::openElement( 'li' ));
			$this->getOutput()->addHTML( $dataTypeId );
			$this->getOutput()->addHTML( Html::closeElement( 'li' ));
		}
		$this->getOutput()->addHTML( Html::closeElement( 'ul' ));
	}
}
