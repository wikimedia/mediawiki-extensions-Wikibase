<?php

namespace Wikibase\Repo\Specials;

use Html;
use HTMLForm;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lib\MessageException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Summary;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\SummaryFormatter;

/**
 * Abstract special page for modifying Wikibase entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Daniel Kinzler
 */
abstract class SpecialModifyEntity extends SpecialWikibaseRepoPage {

	/**
	 * @var EntityDocument|null
	 */
	private $entityForModification = null;

	/**
	 * @var EntityId|null
	 */
	private $entityId;

	/**
	 * @param string $title The title of the special page
	 * @param string[] $tags List of tags to add to edits
	 * @param SpecialPageCopyrightView $copyrightView
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param MediawikiEditEntityFactory $editEntityFactory
	 */
	public function __construct(
		$title,
		array $tags,
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		MediawikiEditEntityFactory $editEntityFactory
	) {
		parent::__construct(
			$title,
			'edit',
			$tags,
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
		} catch ( UnresolvedEntityRedirectException $ex ) {
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
		} catch ( UnresolvedEntityRedirectException $ex ) {
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
		if ( !$this->entityForModification ) {
			$revision = $this->getBaseRevision();
			$this->entityForModification = $revision->getEntity()->copy();
		}

		return $this->entityForModification;
	}

	/**
	 * Returns the EntityDocument that is to be shown by code in this class (or subclasses).
	 * The returns null if no entity ID was specified in the request.
	 *
	 * @throws MessageException
	 * @throws UserInputException
	 *
	 * @return EntityDocument|null
	 */
	protected function getEntityForDisplay() {
		if ( $this->entityId ) {
			$revision = $this->getBaseRevision();
			return $revision->getEntity();
		}

		return null;
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

		try {
			$this->processArguments( $subPage );
			$valid = $this->validateInput();

			if ( $valid && $this->isModificationRequested() ) {
				$updatedEntity = $this->getEntityForModification();
				$summary = $this->modifyEntity( $updatedEntity );

				if ( $summary ) {
					$token = $this->getRequest()->getRawVal( 'wpEditToken' );
					$status = $this->saveEntity( $updatedEntity, $summary, $token );

					$this->handleStatus( $status, $updatedEntity );
					return;
				}
			}

			$entity = $this->getEntityForDisplay();
			$this->setForm( $entity );
		} catch ( UserInputException $ex ) {
			$error = $this->msg( $ex->getKey(), $ex->getParams() )->parse();
			$this->showErrorHTML( $error );
		}
	}

	private function handleStatus( Status $status, EntityDocument $entity ) {
		if ( $status->isOK() ) {
			$entityUrl = $this->getEntityTitle( $this->getEntityId() )->getFullURL();
			$this->getOutput()->redirect( $entityUrl );
		} else {
			$errors = $status->getErrorsArray();
			$this->showErrorHTML( $this->msg( $errors[0][0], array_slice( $errors[0], 1 ) )->parse() );
			$this->setForm( $entity );
		}
	}

	/**
	 * Prepares the arguments.
	 *
	 * @param string|null $subPage
	 */
	protected function processArguments( $subPage ) {
		$parts = $subPage ? explode( '/', $subPage, 2 ) : [];

		$idString = $this->getRequest()->getVal( 'id', $parts[0] ?? null );
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

		if ( !$this->getUser()->isRegistered() ) {
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
			->setWrapperLegend( $this->getDescription() )
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
				'id' => $id,
				'default' => $entity === null ? '' : $entity->getId(),
			],
		];
	}

	/**
	 * Validates form input.
	 *
	 * The default implementation does nothing.
	 * Subclasses should override this to detect otherwise incomplete or erroneous input.
	 *
	 * If this method returns false, the entity should not be updated and the user should be
	 * presented with an input form. Only if it returns true, and isModificationRequested() also
	 * returns true, the entity should be updated in the storage backend.
	 *
	 * @throws UserInputException if any of the provided input is invalid. If the input is
	 *         merely incomplete, no exception should be raised.
	 *
	 * @return bool true if all input needed for modification has been supplied.
	 *         false if the input is valid but incomplete, or if the input is invalid and
	 *         showErrorHTML() has already been called to notify the user of the problem.
	 *         The preferred way of indicating invalid input is however to throw a
	 *         UserInputException.
	 */
	protected function validateInput() {
		return $this->getEntityId() !== null;
	}

	/**
	 * Whether the current request is a request for modification (as opposed to a
	 * request for showing the input form).
	 *
	 * If this method returns false, the entity should not be updated and the user should be
	 * presented with an input form. Only if it returns true, and validateInput() also
	 * returns true, the entity should be updated in the storage backend.
	 *
	 * Undefined before processArguments() was called.
	 *
	 * @return bool
	 */
	protected function isModificationRequested() {
		return $this->getRequest()->wasPosted();
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
