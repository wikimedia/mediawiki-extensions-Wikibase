<?php

/**
 * Abstract class for setting properties of a Wikibase entity.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Denny Vrandecic < denny@vrandecic.de >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialSetEntity extends SpecialWikibasePage {

	/**
	 * Contains pieces of the sub-page name of this special page if a subpage was called.
	 * E.g. array( 'a', 'b' ) in case of 'Special:SetLabel/a/b'
	 * @var string[]
	 */
	protected $parts = null;

	/**
	 * @var string
	 */
	protected $id = null;

	/**
	 * @var EntityContent
	 */
	protected $entityContent = null;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct( $page ) {
		parent::__construct( $page );
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

		$this->parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 3 );
		$this->prepareArguments();

		if ( $this->getRequest()->wasPosted() && $this->getRequest()->getVal( 'token' ) !== null ) {
			if ( $this->hasSufficientArguments() && $this->id !== '' ) {
				$status = $this->modifyEntity( $this->entityContent );
				if ( $status->isOk() ) {
					$editEntity = new \Wikibase\EditEntity( $this->entityContent, $this->getUser() );
					$status = $editEntity->attemptSave( '', EDIT_AUTOSUMMARY, $this->getRequest()->getVal( 'token' ) );
					if ( !$editEntity->isSuccess() ) {
						$editEntity->showErrorPage( $this->getOutput() );
					}
					elseif ( $this->entityContent !== null ) {
						$entityUrl = $this->entityContent->getTitle()->getFullUrl();
						$this->getOutput()->redirect( $entityUrl );
					}
				}
			}
		}
		//$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );
		$this->createForm( $this->getLegend(), $this->additionalFormElements() );
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
	public function createForm( $legend = null, $additionalHtml = '' ) {

		$label = ( $this->label !== '' && $this->langCode !== '' && $this->entityContent !== null )
			? $this->entityContent->getEntity()->getLabel( $this->langCode )
			: $this->label;

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
			. Html::openElement(
				'fieldset',
				array( 'class' => 'wb-fieldset' )
			)
			. Html::element(
				'legend',
				array( 'class' => 'wb-legend' ),
				$legend
			)
			. Html::hidden(
				'token',
				$this->getUser()->getEditToken()
			)
			. $additionalHtml
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
			. Html::closeElement(
				'fieldset'
			)
			. Html::closeElement( 'form' )
		);
	}

	/**
	 * Build additional formelements
	 *
	 * @since 0.1
	 *
	 * @return string Formatted HTML for inclusion in the form
	 */
	protected function additionalFormElements() {
		$html = '';

		if ( $this->parts[0] !== '' ) {
			$html .= Html::input( 'id', htmlspecialchars( $this->id ), 'hidden' );
		}
		else {
			$html .= Html::element( 'br' )
				. Html::element(
					'label',
					array(
						'for' => 'wb-setlanguage-id',
						'class' => 'wb-label'
					),
					$this->msg( 'wikibase-setlabel-id' )->text()
				)
				. Html::input(
					'id',
					htmlspecialchars( $this->id ),
					'text',
					array(
						'id' => 'wb-setlanguage-id',
						'class' => 'wb-input-text wb-input-text-id'
					)
				);
		}

		if ( isset( $this->parts[1] ) && $this->parts[1] !== '' ) {
			$html .= Html::input( 'language', htmlspecialchars( $this->langCode ), 'hidden' );
		}
		else {
			$html .= Html::element( 'br' )
				. Html::element(
					'label',
					array(
						'for' => 'wb-setlanguage-language',
						'class' => 'wb-label'
					),
					$this->msg( 'wikibase-setlabel-language' )->text()
				)
				. Html::input(
					'language',
					htmlspecialchars( $this->langCode ),
					'text',
					array(
						'id' => 'wb-setlanguage-language',
						'class' => 'wb-input-text wb-input-text-language'
					)
				);
		}

		if ( isset( $this->parts[2] ) && $this->parts[2] !== '' ) {
			$html .= Html::input( 'label', htmlspecialchars( $this->label ), 'hidden' );
		}
		else {
			$html .= Html::element( 'br' )
				. Html::element(
					'label',
					array(
						'for' => 'wb-setlanguage-label',
						'class' => 'wb-label'
					),
					$this->msg( 'wikibase-setlabel-label' )->text()
				)
				. Html::input(
					'label',
					htmlspecialchars( $this->label ),
					'text',
					array(
						'id' => 'wb-setlanguage-label',
						'class' => 'wb-input-text wb-input-text-label'
					)
				);
		}

		return $html;

	}
	/**
	 * Tries to extract argument values from web request or of the page's sub-page parts
	 *
	 * @since 0.1
	 */
	protected function prepareArguments() {
		$this->id = $this->getRequest()->getVal( 'id', isset( $this->parts[0] ) ? $this->parts[0] : '' );
		return true;
	}

	/**
	 * Checks whether required arguments are set sufficiently
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	protected function hasSufficientArguments() {
		// Check out label first as this is easy
		if ( $this->label === '' ) {
			return false;
		}
		// Check out lang code
		if ( $this->langCode !== '' ) {
			if ( ( !Language::isValidBuiltInCode( $this->langCode ) or ( !in_array( $this->langCode, \Wikibase\Utils::getLanguageCodes() ) ) ) ) {
				$this->getOutput()->addWikiMsg( 'wikibase-setlabel-invalid-langcode', $this->langCode );
				return false;
			}
		}

		// Check out id and get the asociated content
		// TODO prefix handling should go into EntityContent or EntityHandler
		$prefix = \Wikibase\ItemHandler::singleton()->getEntityPrefix();
		if ( stripos( $this->id, $prefix ) === 0 ) {
			$pureId = strval( substr ( $this->id, strlen( $prefix ) ) );
			// strval returns 0 on everything that is not a number, so check if the number is 0 if it is a prefixed 0 indeed
			if ( ( $pureId === 0 ) && ( strtolower( $this->id ) !== strtolower( $prefix ) . "0" ) ) {
				$this->getOutput()->addWikiMsg( 'wikibase-can-not-find-an-entity' );
				return false;
			}
			else {
				$this->entityContent = \Wikibase\ItemHandler::singleton()->getFromId( $pureId ); // TODO should be EntityContent
			}
		}
		else {
			return false;
		}
		return true;
	}

	/**
	 * Attempt to modify entity
	 *
	 * @since 0.1
	 *
	 * @return Status
	 */
	protected function modifyEntity( \Wikibase\EntityContent &$entityContent ) {
		if ( $this->langCode !== '' || $this->label !== '' ) {
			$entityContent->getEntity()->setLabel( $this->langCode, $this->label );
		}
		return \Status::newGood();
	}

	/**
	 * Get legend
	 *
	 * @since 0.1
	 *
	 * @return string Legend for the fieldset
	 */
	 protected function getLegend() {
	 	return "somestring";
	 }

}