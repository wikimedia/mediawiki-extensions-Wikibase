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

		$view = new Wikibase\ItemView( $this->getContext() );
		$view->render( Wikibase\ItemContent::newEmpty(), $this->getOutput(), $options );
	}
}
