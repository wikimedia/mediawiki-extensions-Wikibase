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
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
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

		$request = $this->getRequest();
		$parts = ( $this->subPage === '' ) ? array() : explode( '/', $this->subPage, 2 );
		$label = $request->getVal( 'label', isset( $parts[0] ) ? $parts[0] : '' );
		$description = $request->getVal( 'description', isset( $parts[1] ) ? $parts[1] : '' );

		if ( ( isset( $label ) && $label != '' ) || ( isset( $description ) && $description != '' ) ) {
			// TODO: sanity check, escaping, etc..
			$lang = $this->getLanguage()->getCode();
			$itemContent = \Wikibase\ItemContent::newEmpty();
			$itemContent->getEntity()->setLabel( $lang, $label );
			$itemContent->getEntity()->setDescription( $lang, $description );
			$editEntity = new \Wikibase\EditEntity( $itemContent );
			$editEntity->attemptSave( '' );
			// TODO: check returned status & handle case when saving failed

			$itemContent = \Wikibase\ItemHandler::singleton()->getFromId( $itemContent->getItem()->getId() );
			if ( $itemContent !== null ) {
				$itemUrl = $itemContent->getTitle()->getFullUrl();
				$this->getOutput()->redirect( $itemUrl );
			}
			// TODO: return to form on failure
		} else {
			$this->createItemForm( $label, $description );
		}
	}

	public function createItemForm( $label, $description ) {
		$this->getOutput()->addHTML(
				$this->msg( 'wikibase-createitem-intro' )->text()
				. Html::openElement( 'form', array( 'method' => 'get', 'action' => "", 'name' => 'createitem', 'id' => 'mw-createitem-form1' ) )
				. Xml::fieldset( $this->msg( 'wikibase-createitem-fieldset' )->text() )
				. Xml::inputLabel( $this->msg( 'wikibase-createitem-label' )->text(), 'label', '', 12, $label ? htmlspecialchars( $label ) : '' )
				. Xml::closeElement('br')
				. Xml::inputLabel( $this->msg( 'wikibase-createitem-description' )->text(), 'description', '', 36, $description ? htmlspecialchars( $description ) : '' )
				. Xml::closeElement( 'br' )
				. Xml::submitButton( $this->msg( 'wikibase-createitem-submit' )->text() )
				. Html::closeElement( 'fieldset' )
				. Html::closeElement( 'form' )
		);
	}
}
