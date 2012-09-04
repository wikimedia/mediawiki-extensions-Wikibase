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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 */
class SpecialCreateItem extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'CreateItem' );
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
		$this->setHeaders();
		$this->checkPermissions();
		$this->outputHeader();
		$options = ParserOptions::newFromContext( $this->getContext() );
		$options->setEditSection( true ); //NOTE: editing must be enabled
        $this->createItemForm( $options );
  }

	public function createItemForm( $options ) {
		$this->getOutput()->addHTML(
			$this->msg( 'wikibase-createitem-intro' )->text()
				. Html::openElement( 'form', array( 'method' => 'get', 'action' => "", 'name' => 'createitem', 'id' => 'mw-createitem-form1' ) )
				. Xml::fieldset( $this->msg( 'wikibase-createitem-fieldset' )->text() )
				. Xml::inputLabel( $this->msg( 'wikibase-createitem-label' )->text(), '', '', 12, '' )
				. Xml::closeElement('br')
				. Xml::inputLabel( $this->msg( 'wikibase-createitem-description' )->text(), '', '', 36, '' )
				. Xml::closeElement( 'br' )
				. Xml::submitButton( $this->msg( 'wikibase-createitem-submit' )->text() )
				. Html::closeElement( 'fieldset' )
				. Html::closeElement( 'form' )
		);
	}
}
