<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Html;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\EntityRevision;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Abstract special page for modifying Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Daniel Kinzler
 */
abstract class SpecialModifyEntity extends SpecialWikibaseRepoPage {

	/**
	 * @since 0.5
	 *
	 * @var EntityRevision|null
	 */
	protected $entityRevision = null;

	/**
	 * @var string
	 */
	private $rightsUrl;

	/**
	 * @var string
	 */
	private $rightsText;

	/**
	 * @since 0.4
	 *
	 * @param string $title The title of the special page
	 * @param string $restriction The required user right, 'edit' per default.
	 */
	public function __construct( $title, $restriction = 'edit' ) {
		parent::__construct( $title, $restriction );

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();

		$this->rightsUrl = $settings->getSetting( 'dataRightsUrl' );
		$this->rightsText = $settings->getSetting( 'dataRightsText' );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.4
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

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

		$summary = false;
		$valid = $this->validateInput();
		$entity = $this->entityRevision === null ? null : $this->entityRevision->getEntity();

		if ( $valid ) {
			$summary = $this->modifyEntity( $entity );
		}

		if ( !$summary ) {
			$this->setForm( $entity );
		} else {
			//TODO: Add conflict detection. All we need to do is to provide the base rev from
			// $this->entityRevision to the saveEntity() call. But we need to make sure
			// conflicts are reported in a nice way first. In particular, we'd want to
			// show the form again.
			$status = $this->saveEntity( $entity, $summary, $this->getRequest()->getVal( 'wpEditToken' ) );

			if ( !$status->isOK() && $status->getErrorsArray() ) {
				$errors = $status->getErrorsArray();
				$this->showErrorHTML( $this->msg( $errors[0][0], array_slice( $errors[0], 1 ) )->parse() );
				$this->setForm( $entity );
			} else {
				$entityUrl = $this->getEntityTitle( $entity->getId() )->getFullUrl();
				$this->getOutput()->redirect( $entityUrl );
			}
		}
	}

	/**
	 * Prepares the arguments.
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	protected function prepareArguments( $subPage ) {
		$parts = $subPage === '' ? array() : explode( '/', $subPage, 2 );

		$idString = $this->getRequest()->getVal( 'id', isset( $parts[0] ) ? $parts[0] : null );

		if ( !$idString ) {
			return;
		}

		$entityId = $this->parseEntityId( $idString );
		$this->entityRevision = $this->loadEntity( $entityId );
	}

	/**
	 * @todo could factor this out into a special page form builder and renderer
	 */
	private function addCopyrightText() {
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$this->rightsUrl,
			$this->rightsText
		);

		$submitKey = 'wikibase-' . strtolower( $this->getName() ) . '-submit';
		$html = $copyrightView->getHtml( $this->getLanguage(), $submitKey );
		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Building the HTML form for modifying an entity.
	 *
	 * @param Entity $entity
	 */
	private function setForm( Entity $entity = null ) {
		$this->addCopyrightText();

		$this->getOutput()->addModuleStyles( array( 'wikibase.special' ) );

		if ( $this->getUser()->isAnon() ) {
			$this->showErrorHTML(
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-item' )->text()
				)->parse(),
				'warning'
			);
		}

		$formDescriptor = array();
		// Form elements
		list( $intro, $formElements ) = $this->getFormElements( $entity );
		$formDescriptor = array_merge( $formDescriptor, $formElements );
		$submitKey = 'wikibase-' . strtolower( $this->getName() ) . '-submit';
		$formDescriptor[] = array(
			'type' => 'submit',
			'id' => 'wb-' . strtolower( $this->getName() ) . '-submit',
			'cssclass' => 'wb-button',
			'name' => $submitKey,
			'default' => $this->msg( $submitKey )->text()
		);
		$sectionName = strtolower( $this->getName() );
		foreach ( $formDescriptor as $name => $field ) {
			$formDescriptor[$name]['section'] = $sectionName;
		}
		$htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'special' );
		$htmlForm
			->setId( 'wb-' . strtolower( $this->getName() ) . '-form1' )
			->setHeaderText( Html::rawElement( 'p', array(), $intro ), $sectionName )
			->addHiddenField( 'wpEditToken', $this->getUser()->getEditToken() )
			->suppressDefaultSubmit()
			->setSubmitCallback( array( $this, 'noop' ) ) // dummy
			->show();
	}

	/**
	 * Does nothing.
	 */
	public function noop() {
	}

	/**
	 * Returns the form elements.
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @return string HTML
	 */
	protected function getFormElements( Entity $entity = null ) {
		$id = 'wb-modifyentity-id';

		return array(
			'id' => array(
				'name' => 'id',
				'label-message' => 'wikibase-modifyentity-id',
				'type' => 'text',
				'cssclass' => 'wb-input',
				'id' => $id,
				'default' => $entity === null ? '' : $entity->getId(),
				'cssclass' => 'wb-input'
			),
		);
	}

	/**
	 * Validates form input.
	 *
	 * The default implementation just checks whether a target entity was specified via a POST request.
	 * Subclasses should override this to detect otherwise incomplete or erroneous input.
	 *
	 * @since 0.5
	 *
	 * @return bool true if the form input is ok and normal processing should
	 * continue by calling modifyEntity().
	 */
	protected function validateInput() {
		return $this->entityRevision !== null && $this->getRequest()->wasPosted();
	}

	/**
	 * Modifies the entity.
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @return Summary|bool
	 */
	abstract protected function modifyEntity( Entity $entity );

	/**
	 * Applies the given ChangeOp to the given Entity.
	 * If validation fails, a ChangeOpValidationException is thrown.
	 *
	 * @since 0.5
	 *
	 * @param ChangeOp $changeOp
	 * @param Entity $entity
	 * @param Summary $summary The summary object to update with information about the change.
	 *
	 * @throws ChangeOpException
	 */
	protected function applyChangeOp( ChangeOp $changeOp, Entity $entity, Summary $summary = null ) {
		$result = $changeOp->validate( $entity );

		if ( !$result->isValid() ) {
			throw new ChangeOpValidationException( $result );
		}

		$changeOp->apply( $entity, $summary );
	}

}
