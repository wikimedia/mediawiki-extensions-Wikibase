<?php

namespace Wikibase\Repo\Specials;

use Html;
use UserInputException;
use Wikibase\EditEntity;
use Wikibase\Summary;

/**
 * Abstract special page for modifying Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
abstract class SpecialModifyEntity extends SpecialWikibaseRepoPage {

	/**
	 * The entity content to modify.
	 *
	 * @since 0.4
	 *
	 * @var \Wikibase\EntityContent
	 */
	protected $entityContent;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param string $title The title of the special page
	 * @param string $restriction The required user right, 'edit' per default.
	 */
	public function __construct( $title, $restriction = 'edit' ) {
		parent::__construct( $title, $restriction );
	}

	/**
	 * Main method
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 *
	 * @return boolean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkReadOnly();

		$this->setHeaders();
		$this->outputHeader();

		try {
			$this->prepareArguments( $subPage );
		} catch ( UserInputException $ex ) {
			$error = $this->msg( $ex->getKey(), $ex->getParams() )->parse();
			$this->showErrorHTML( $error );
		}

		$summary = $this->modifyEntity();

		if ( $summary === false ) {
			$this->setForm();
		}
		else {
			// TODO: need conflict detection??
			$editEntity = new EditEntity( $this->entityContent, $this->getUser(), false, $this->getContext() );
			$editEntity->attemptSave(
				$this->summaryFormatter->formatSummary( $summary ),
				EDIT_UPDATE,
				$this->getRequest()->getVal( 'wpEditToken' )
			);

			if ( !$editEntity->isSuccess() && $editEntity->getStatus()->getErrorsArray() ) {
				$errors = $editEntity->getStatus()->getErrorsArray();
				$this->showErrorHTML( $this->msg( $errors[0][0], array_slice( $errors[0], 1 ) )->parse() );
				$this->setForm();
			}
			else {
				$entityUrl = $this->entityContent->getTitle()->getFullUrl();
				$this->getOutput()->redirect( $entityUrl );
			}
		}

		return true;
	}

	/**
	 * Prepares the arguments.
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	protected function prepareArguments( $subPage ) {
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

		// Get id
		$rawId = $this->getRequest()->getVal( 'id', isset( $parts[0] ) ? $parts[0] : null );

		if ( !$rawId ) {
			return;
		}

		$id = $this->parseEntityId( $rawId );

		$this->entityContent = $this->loadEntityContent( $id );
	}

	/**
	 * Building the HTML form for modifying an entity.
	 *
	 * @since 0.2
	 */
	private function setForm() {
		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		// FIXME: Edit warning should be displayed above the license note like on "New Entity" page.
		// (Unfortunately, the license note is generated in SpecialModifyTerm::modifyEntity.)
		if ( $this->getUser()->isAnon() ) {
			$this->showErrorHTML(
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-item' )->text()
				)->parse(),
				'warning'
			);
		}

		// Form header
		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getPageTitle()->getFullUrl(),
					'name' => strtolower( $this->getName() ),
					'id' => 'wb-' . strtolower( $this->getName() ) . '-form1',
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
				$this->msg( 'special-' . strtolower( $this->getName() ) )->text()
			)
		);

		// Form elements
		$this->getOutput()->addHTML( $this->getFormElements() );

		// Form body
		$this->getOutput()->addHTML(
			Html::input(
				'wikibase-' . strtolower( $this->getName() ) . '-submit',
				$this->msg( 'wikibase-' . strtolower( $this->getName() ) . '-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-' . strtolower( $this->getName() ) . '-submit',
					'class' => 'wb-button'
				)
			)
			. Html::input(
				'wpEditToken',
				$this->getUser()->getEditToken(),
				'hidden'
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

	/**
	 * Returns the form elements.
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getFormElements() {
		$id = $this->entityContent ?
			$this->entityContent->getEntity()->getId()->getSerialization() : '';

		return Html::element(
			'label',
			array(
				'for' => 'wb-modifyentity-id',
				'class' => 'wb-label'
			),
			$this->msg( 'wikibase-modifyentity-id' )->text()
		)
		. Html::input(
			'id',
			$id,
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wb-modifyentity-id'
			)
		)
		. Html::element( 'br' );
	}

	/**
	 * Returns the summary for the given module.
	 *
	 * @param string|null $module
	 *
	 * @return Summary
	 */
	protected function getSummary( $module = null ) {
		return new Summary( $module );
	}

	/**
	 * Modifies the entity. A return value of false indicates that the edit failed.
	 *
	 * @since 0.4
	 *
	 * @return Summary|boolean The summary or false
	 */
	abstract protected function modifyEntity();
}
