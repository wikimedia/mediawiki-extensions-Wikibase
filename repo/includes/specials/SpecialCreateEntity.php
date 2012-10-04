<?php

use Wikibase\Entity, Wikibase\EntityContent;

/**
 * Page for creating new Wikibase entities.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author John Erling Blad < jeblad@gmail.com >
 */
abstract class SpecialCreateEntity extends SpecialWikibasePage {

	/**
	 * Contains pieces of the sub-page name of this special page if a subpage was called.
	 * E.g. array( 'a', 'b' ) in case of 'Special:CreateEntity/a/b'
	 * @var string[]
	 */
	protected $parts = null;

	/**
	 * @var string
	 */
	protected $label = null;

	/**
	 * @var string
	 */
	protected $description = null;

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

		$this->parts = ( $subPage === '' ? array() : explode( '/', $subPage ) );
		$this->prepareArguments();

		if ( $this->getRequest()->wasPosted()
			&&  $this->getUser()->matchEditToken( $this->getRequest()->getVal( 'token' ) ) ) {

			if ( $this->hasSufficientArguments() ) {
				$entityContent = $this->createEntity();

				$status = $this->modifyEntity( $entityContent );

				if ( $status->isOk() ) {
					$editEntity = new \Wikibase\EditEntity( $entityContent, $this->getUser() );
					$editEntity->attemptSave( '', EDIT_AUTOSUMMARY|EDIT_NEW, $this->getRequest()->getVal( 'token' ) );

					if ( !$editEntity->isSuccess() ) {
						$editEntity->showStatus( $this->getOutput() );
					} elseif ( $entityContent !== null ) {
						$entityUrl = $entityContent->getTitle()->getFullUrl();
						$this->getOutput()->redirect( $entityUrl );
					}
				}
			}
		}

		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );
		$this->createForm( $this->getLegend(), $this->additionalFormElements() );
	}

	/**
	 * Tries to extract argument values from web request or of the page's sub-page parts
	 *
	 * @since 0.1
	 */
	protected function prepareArguments() {
		$this->label = $this->getRequest()->getVal( 'label', isset( $this->parts[0] ) ? $this->parts[0] : '' );
		$this->description = $this->getRequest()->getVal( 'description', isset( $this->parts[1] ) ? $this->parts[1] : '' );
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
		return $this->label !== '' || $this->description !== '';
	}

	/**
	 * Create entity content
	 *
	 * @since 0.1
	 *
	 * @return Entity Created entity content of correct subtype
	 */
	abstract protected function createEntity();

	/**
	 * Attempt to modify entity
	 *
	 * @since 0.1
	 *
	 * @return Status
	 */
	protected function modifyEntity( \Wikibase\EntityContent &$entity ) {
		$lang = $this->getLanguage()->getCode();
		if ( $this->label !== '' ) {
			$entity->getEntity()->setLabel( $lang, $this->label );
		}
		if ( $this->description !== '' ) {
			$entity->getEntity()->setDescription( $lang, $this->description );
		}
		return \Status::newGood();
	}

	/**
	 * Build additional formelements
	 *
	 * @since 0.1
	 *
	 * @return string Formatted HTML for inclusion in the form
	 */
	protected function additionalFormElements() {
		return Html::element(
			'label',
			array(
				'for' => 'wb-createentity-label',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-createentity-label' )->text()
		)
		. Html::input(
			'label',
			$this->label ? htmlspecialchars( $this->label ) : '',
			'text',
			array(
				'id' => 'wb-createentity-label',
				'size' => 12,
				'class' => 'wb-input'
			)
		)
		. Html::element( 'br' )
		. Html::element(
			'label',
			array(
				'for' => 'wb-createentity-description',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-createentity-description' )->text()
		)
		. Html::input(
			'description',
			$this->description ? htmlspecialchars( $this->description ) : '',
			'text',
			array(
				'id' => 'wb-createentity-description',
				'size' => 36,
				'class' => 'wb-input'
			)
		)
		. Html::element( 'br' );
	}

	/**
	 * Building the HTML form for creating a new item.
	 *
	 * @since 0.1
	 *
	 * @param string $label initial value for the label input box
	 * @param string $description initial value for the description input box
	 */
	public function createForm( $legend = null, $additionalHtml = '' ) {
		$this->getOutput()->addHTML(
		/*
				$this->msg( 'wikibase-createentity-intro' )->params(
					Language::fetchLanguageName( $this->getLanguage()->getCode() )
				)->text()
				. */ Html::openElement(
					'form',
					array(
						'method' => 'post',
						'action' => $this->getTitle()->getFullUrl(),
						'name' => 'createentity',
						'id' => 'mw-createentity-form1',
						'class' => 'wb-form'
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
				. Html::input(
					'submit',
					$this->msg( 'wikibase-createentity-submit' )->text(),
					'submit',
					array(
						'id' => 'wb-createentiy-submit',
						'class' => 'wb-button'
					)
				)
				. Html::closeElement( 'fieldset' )
				. Html::closeElement( 'form' )
		);
	}

	/**
	 * Get legend
	 *
	 * @since 0.1
	 *
	 * @return string Legend for the fieldset
	 */
	abstract protected function getLegend();

}
