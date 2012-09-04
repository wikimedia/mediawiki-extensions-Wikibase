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
		$this->outputHeader();

		$request = $this->getRequest();
		$parts = ( $this->subPage === '' ) ? array() : explode( '/', $this->subPage, 2 );
		$label = $request->getVal( 'label', isset( $parts[0] ) ? $parts[0] : '' );
		$description = $request->getVal( 'description', isset( $parts[1] ) ? $parts[1] : '' );

		if ( ( isset( $label ) && $label != '' ) || ( isset( $description ) && $description != '' ) ) {
			$lang = $this->getLanguage()->getCode();
			$itemContent = \Wikibase\ItemContent::newEmpty();
			$itemContent->getEntity()->setLabel( $lang, $label );
			$itemContent->getEntity()->setDescription( $lang, $description );
			$editEntity = new \Wikibase\EditEntity( $itemContent, $this->getUser() );
			$status = $editEntity->attemptSave( '', EDIT_AUTOSUMMARY|EDIT_NEW );
			if ( !$editEntity->isSuccess() ) {
				$editEntity->showErrorPage( $this->getOutput() );
			} else if ( $itemContent !== null ) {
				$itemUrl = $itemContent->getTitle()->getFullUrl();
				$this->getOutput()->redirect( $itemUrl );
			}
		}
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );
		$this->createItemForm( $label, $description );
	}

	public function createItemForm( $label, $description ) {
		$this->getOutput()->addHTML(
				$this->msg( 'wikibase-createitem-intro' )->params( 
					Language::fetchLanguageName( $this->getLanguage()->getCode() )
				)->text()
				. Html::openElement(
					'form',
					array(
						'method' => 'get',
						'action' => '',
						'name' => 'createitem',
						'id' => 'mw-createitem-form1'
					)
				)
				. Xml::fieldset( $this->msg( 'wikibase-createitem-fieldset' )->text() )
				. Xml::inputLabel(
					$this->msg( 'wikibase-createitem-label' )->text(),
					'label',
					'wb-createitem-label',
					12,
					$label ? htmlspecialchars( $label ) : '',
					array( 'class' => 'wb-input-text wb-input-text-label' )
				)
				. Xml::closeElement( 'br' )
				. Xml::inputLabel(
					$this->msg( 'wikibase-createitem-description' )->text(),
					'description',
					'wb-createitem-description',
					36,
					$description ? htmlspecialchars( $description ) : '',
					array( 'class' => 'wb-input-text' )
				)
				. Xml::closeElement( 'br' )
				. Xml::submitButton(
					$this->msg( 'wikibase-createitem-submit' )->text(),
					array(
						'id' => 'wb-createitem-submit',
						'class' => 'wb-input-button'
					)
				)
				. Html::closeElement( 'fieldset' )
				. Html::closeElement( 'form' )
		);
	}
}
