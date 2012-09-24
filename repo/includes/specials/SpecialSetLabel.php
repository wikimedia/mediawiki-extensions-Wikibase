<?php

/**
 * Special page for setting the label of a Wikibase entity.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Denny Vrandecic < denny@vrandecic.de >
 */
class SpecialSetLabel extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'SetLabel' );
	}

	/**
	 * Main method.
	 *
	 * @since 0.2
	 *
	 * @param string|null $subPage
	 *
	 * @return boolean
	 */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->outputHeader();

		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

		// Setup
		// TODO make this work for all types of entities

		// Get entity
		$id = $request->getVal( 'id', isset( $parts[0] ) ? $parts[0] : '' );
		if ( $id === '' ) {
			$id = null;
		}
		$entityContent = null;
		if ( $id !== null ) {
			// TODO prefix handling should go into EntityContent or EntityHandler
			$prefix = \Wikibase\ItemHandler::singleton()->getEntityPrefix();
			if ( stripos( $id, $prefix ) === 0 ) {
				$pureId = strval( substr ( $id, strlen( $prefix ) ) );
				// strval returns 0 on everything that is not a number, so check if the number is 0 if it is a prefixed 0 indeed
				if ( ( $pureId === 0 ) && ( strtolower( $id ) !== strtolower( $prefix ) . "0" ) ) {
					$entityContent = null;
				} else {
					$entityContent = \Wikibase\ItemHandler::singleton()->getFromId( $pureId ); // TODO should be EntityContent
				}
			}
		}

		// Get language
		$language = $request->getVal( 'language', isset( $parts[1] ) ? $parts[1] : '' );
		if ( $language === '') {
			$language = null;
		}
		if ( $language !== null ) {
			if ( ( !Language::isValidBuiltInCode( $language ) or ( !in_array( $language, \Wikibase\Utils::getLanguageCodes() ) ) ) ) {
				$this->getOutput()->addWikiMsg( 'wikibase-setlabel-invalid-langcode', $language );
				$language = null;
			}
		}

		// Get label
		$label = $request->getVal( 'label', '' );
		if ( $label === '' ) {
			$label = null;
		}

		if ( ( $label !== null ) && ( $entityContent !== null ) && ( $language !== null ) && $this->getRequest()->wasPosted() ) {
			// TODO check about token?
			$entityContent->getEntity()->setLabel( $language, $label );
			$editEntity = new \Wikibase\EditEntity( $entityContent, $this->getUser() );
			$status = $editEntity->attemptSave( '', EDIT_AUTOSUMMARY ); // TODO Check this line and the following
			if ( !$editEntity->isSuccess() ) {
				$editEntity->showErrorPage( $this->getOutput() );
			} else if ( $entityContent !== null ) {
				$entityUrl = $entityContent->getTitle()->getFullUrl();
				$this->getOutput()->redirect( $entityUrl );
			}
		} else {
			$this->setLabelForm( $entityContent, $language );
		}
	}

	/**
	 * Building the HTML form for setting the label of an entity. If the entity and the language are already given,
	 * the form will only ask for the label. If not, a complete form is being shown.
	 *
	 * @since 0.2
	 *
	 * @param \Wikibase\EntityContent|null $entityContent the entity to have the label set
	 * @param string|null $language language code for the label
	 */
	public function setLabelForm( $entityContent, $language ) {

		$label = $entityContent ? $entityContent->getEntity()->getLabel( $language ) : '';

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getTitle()->getFullUrl(),
					'name' => 'setlabel',
					'id' => 'wb-setlabel-form1'
				)
			)
		);

		if ( ( $entityContent !== null ) && ( $language !== null ) ) {
			$this->getOutput()->addWikiMsg(
				'wikibase-setlabel-introfull',
				$entityContent->getTitle()->getPrefixedText(),
				\Language::fetchLanguageName( $language, $this->getLanguage()->getCode() )
			);
			$this->getOutput()->addHTML(
				Html::input( 'language', $language, 'hidden' )
				. Html::input( 'id', $entityContent->getTitle()->getText(), 'hidden' )
			);
		} else {
			$id = $entityContent ? $entityContent->getTitle()->getText() : '';
			$language = $language ? $language : $this->getLanguage()->getCode();
			$this->getOutput()->addHTML(
				$this->msg( 'wikibase-setlabel-intro' )->text()
				. Html::element( 'br' )
				. $this->msg( 'wikibase-setlabel-id' )->text()
				. Html::element( 'br' )
				. Html::input(
					'id',
					$id,
					'text'
				)
				. Html::element( 'br' )
				. $this->msg( 'wikibase-setlabel-language' )->text()
				. Html::element( 'br' )
				. Html::input(
					'language',
					$language,
					'text'
				)
				. Html::element( 'br' )
				. $this->msg( 'wikibase-setlabel-label' )->text()
				. Html::element( 'br' )
			);
		}

		$this->getOutput()->addHTML(
			Html::input(
				'label',
				htmlspecialchars( $label ),
				'text',
				array( 'class' => 'wb-input-text wb-input-text-label' )
			)
			. Html::element( 'br' )
			. Html::input(
				'wikibase-setlabel-submit',
				$this->msg( 'wikibase-setlabel-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-setlabel-submit',
					'class' => 'wb-input-button'
				)
			)
			. Html::input(
				'token',
				$this->getUser()->getToken(),
				'hidden'
			)
			. Html::closeElement( 'form' )
		);
	}

}