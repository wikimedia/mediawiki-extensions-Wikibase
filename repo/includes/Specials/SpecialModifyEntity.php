<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Html;
use MWException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\EditEntityFactory;
use Wikibase\EntityRevision;
use Wikibase\Lib\MessageException;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\UserInputException;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Abstract special page for modifying Wikibase entity.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Daniel Kinzler
 */
abstract class SpecialModifyEntity extends SpecialWikibaseRepoPage {

	/**
	 * @var EntityDocument|null
	 */
	private $entity = null;

	/**
	 * @var EntityId|null
	 */
	private $entityId;

	/**
	 * @param string $title The title of the special page
	 * @param SpecialPageCopyrightView $copyrightView
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EditEntityFactory $editEntityFactory
	 */
	public function __construct(
		$title,
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory
	) {
		parent::__construct(
			$title,
			'edit',
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory
		);
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Returns the ID of the Entity being modified.
	 * Returns null if no entity ID was specified in the request.
	 *
	 * @note The return value is undefined before prepareArguments() has been called.
	 *
	 * @return null|EntityId
	 * @throws \MWException
	 */
	protected function getEntityId() {
		return $this->entityId;
	}

	/**
	 * Returns the base revision. If no base revision ID was passed to prepareEditEntity(),
	 * this returns the latest revision.
	 *
	 * @throws UserInputException
	 *
	 * @return EntityRevision
	 */
	protected function getBaseRevision() {
		$id = $this->getEntityId();
		try {
			$baseRev = $this->getEditEntity()->getBaseRevision();

			if ( $baseRev === null ) {
				throw new UserInputException(
					'wikibase-wikibaserepopage-invalid-id',
					[ $id->getSerialization() ],
					'Entity ID "' . $id->getSerialization() . '" is unknown'
				);
			}
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-unresolved-redirect',
				[ $id->getSerialization() ],
				'Entity ID "' . $id->getSerialization() . '"" refers to a redirect'
			);
		} catch ( StorageException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-storage-exception',
				[ $id->getSerialization(), $ex->getMessage() ],
				'Entity "' . $id->getSerialization() . '" could not be loaded'
			);
		}

		return $baseRev;
	}

	/**
	 * Returns the current revision.
	 *
	 * @throws UserInputException
	 *
	 * @return null|EntityRevision
	 */
	protected function getLatestRevision() {
		$id = $this->getEntityId();
		try {
			$baseRev = $this->getEditEntity()->getLatestRevision();

			if ( $baseRev === null ) {
				throw new UserInputException(
					'wikibase-wikibaserepopage-invalid-id',
					[ $id->getSerialization() ],
					'Entity ID "' . $id->getSerialization() . '" is unknown'
				);
			}
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-unresolved-redirect',
				[ $id->getSerialization() ],
				'Entity ID "' . $id->getSerialization() . '"" refers to a redirect'
			);
		} catch ( StorageException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-storage-exception',
				[ $id->getSerialization(), $ex->getMessage() ],
				'Entity "' . $id->getSerialization() . '" could not be loaded'
			);
		}

		return $baseRev;
	}

	/**
	 * Returns the EntityDocument that is to be modified by code in this class (or subclasses).
	 * The first call to this method calls getBaseRevision().
	 *
	 * @throws MessageException
	 * @throws UserInputException
	 *
	 * @return EntityDocument
	 */
	protected function getEntityForModification() {
		if ( !$this->entity ) {
			$revision = $this->getBaseRevision();
			$this->entity = $revision->getEntity()->copy();
		}

		return $this->entity;
	}

	/**
	 * @see SpecialWikibasePage::execute
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

		$summary = false;
		$entity = null;

		try {
			$this->prepareArguments( $subPage );

			$valid = $this->validateInput();
			$entity = $this->getEntityId() ? $this->getEntityForModification() : null;

			if ( $valid && $entity ) {
				$summary = $this->modifyEntity( $entity );
			}
		} catch ( UserInputException $ex ) {
			$error = $this->msg( $ex->getKey(), $ex->getParams() )->parse();
			$this->showErrorHTML( $error );
		}

		if ( !$entity || !$summary ) {
			$this->setForm( $entity );
		} else {
			$status = $this->saveEntity( $entity, $summary, $this->getRequest()->getVal( 'wpEditToken' ) );

			if ( !$status->isOK() && $status->getErrorsArray() ) {
				$errors = $status->getErrorsArray();
				$this->showErrorHTML( $this->msg( $errors[0][0], array_slice( $errors[0], 1 ) )->parse() );
				$this->setForm( $entity );
			} else {
				$entityUrl = $this->getEntityTitle( $entity->getId() )->getFullURL();
				$this->getOutput()->redirect( $entityUrl );
			}
		}
	}

	/**
	 * Prepares the arguments.
	 *
	 * @param string|null $subPage
	 */
	protected function prepareArguments( $subPage ) {
		$parts = $subPage === '' ? [] : explode( '/', $subPage, 2 );

		$idString = $this->getRequest()->getVal( 'id', isset( $parts[0] ) ? $parts[0] : null );
		$baseRevId = $this->getRequest()->getInt( 'revid', 0 );

		if ( !$idString ) {
			return;
		}

		$this->entityId = $this->parseEntityId( $idString );

		$this->prepareEditEntity( $this->entityId, $baseRevId );
	}

	/**
	 * Return the HTML form.
	 *
	 * @param EntityDocument|null $entity
	 *
	 * @return HTMLForm
	 */
	abstract protected function getForm( EntityDocument $entity = null );

	/**
	 * Building the HTML form for modifying an entity.
	 *
	 * @param EntityDocument|null $entity
	 */
	private function setForm( EntityDocument $entity = null ) {
		$this->getOutput()->addHTML( $this->getCopyrightHTML() );

		$this->getOutput()->addModuleStyles( [ 'wikibase.special' ] );

		if ( $this->getUser()->isAnon() ) {
			$this->getOutput()->addHTML( Html::rawElement(
				'p',
				[ 'class' => 'warning' ],
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-item' )->text()
				)->parse()
			) );
		}

		$submitKey = 'wikibase-' . strtolower( $this->getName() ) . '-submit';

		$this->getForm( $entity )
			->setId( 'wb-' . strtolower( $this->getName() ) . '-form1' )
			->setSubmitID( 'wb-' . strtolower( $this->getName() ) . '-submit' )
			->setSubmitName( $submitKey )
			->setSubmitTextMsg( $submitKey )
			->setWrapperLegendMsg( 'special-' . strtolower( $this->getName() ) )
			->setSubmitCallback( function () {
				// no-op
			} )->show();
	}

	/**
	 * @param EntityDocument|null $entity
	 *
	 * @return array
	 */
	protected function getFormElements( EntityDocument $entity = null ) {
		$id = 'wb-modifyentity-id';

		return [
			'id' => [
				'name' => 'id',
				'label-message' => 'wikibase-modifyentity-id',
				'type' => 'text',
				'cssclass' => 'wb-input',
				'id' => $id,
				'default' => $entity === null ? '' : $entity->getId(),
			],
		];
	}

	/**
	 * Validates form input.
	 *
	 * The default implementation just checks whether a target entity was specified via a POST request.
	 * Subclasses should override this to detect otherwise incomplete or erroneous input.
	 *
	 * @return bool true if the form input is ok and normal processing should
	 * continue by calling modifyEntity().
	 */
	protected function validateInput() {
		return $this->getEntityId() !== null && $this->getRequest()->wasPosted();
	}

	/**
	 * Modifies the entity.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Summary|bool
	 */
	abstract protected function modifyEntity( EntityDocument $entity );

	/**
	 * Applies the given ChangeOp to the given Entity.
	 * If validation fails, a ChangeOpValidationException is thrown.
	 *
	 * @param ChangeOp $changeOp
	 * @param EntityDocument $entity
	 * @param Summary|null $summary The summary object to update with information about the change.
	 *
	 * @throws ChangeOpException
	 */
	protected function applyChangeOp( ChangeOp $changeOp, EntityDocument $entity, Summary $summary = null ) {
		// NOTE: always validate modification against the current revision!
		// TODO: this should be re-engineered, see T126231
		$currentEntityRevision = $this->getLatestRevision();
		$result = $changeOp->validate( $currentEntityRevision->getEntity() );

		if ( !$result->isValid() ) {
			throw new ChangeOpValidationException( $result );
		}

		$changeOp->apply( $entity, $summary );
	}

}
