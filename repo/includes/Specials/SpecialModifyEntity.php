<?php

namespace Wikibase\Repo\Specials;

use Html;
use SiteStore;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EditEntityFactory;
use Wikibase\EntityRevision;
use Wikibase\Lib\MessageException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Abstract special page for modifying Wikibase entity.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Daniel Kinzler
 */
abstract class SpecialModifyEntity extends SpecialWikibaseRepoPage {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

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

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$settings = $wikibaseRepo->getSettings();

		$this->rightsUrl = $settings->getSetting( 'dataRightsUrl' );
		$this->rightsText = $settings->getSetting( 'dataRightsText' );

		$this->setSpecialModifyEntityServices(
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->newEditEntityFactory( $this->getContext() )
		);
	}

	/**
	 * Override services (for testing).
	 *
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SiteStore $siteStore
	 * @param EditEntityFactory $editEntityFactory
	 */
	public function setSpecialModifyEntityServices(
		SummaryFormatter $summaryFormatter,
		EntityRevisionLookup $entityRevisionLookup,
		EntityTitleLookup $entityTitleLookup,
		SiteStore $siteStore,
		EditEntityFactory $editEntityFactory
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->setSpecialWikibaseRepoPageServices(
			$summaryFormatter,
			$entityTitleLookup,
			$siteStore,
			$editEntityFactory
		);
	}

	public function doesWrites() {
		return true;
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
				$entityUrl = $this->getEntityTitle( $entity->getId() )->getFullURL();
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
	 * Loads the entity for this entity id.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $id
	 * @param int|string $revisionId
	 *
	 * @throws MessageException
	 * @throws UserInputException
	 * @return EntityRevision
	 */
	protected function loadEntity( EntityId $id, $revisionId = EntityRevisionLookup::LATEST_FROM_MASTER ) {
		try {
			$entity = $this->entityRevisionLookup->getEntityRevision( $id, $revisionId );

			if ( $entity === null ) {
				throw new UserInputException(
					'wikibase-wikibaserepopage-invalid-id',
					array( $id->getSerialization() ),
					'Entity id is unknown'
				);
			}
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-unresolved-redirect',
				array( $id->getSerialization() ),
				'Entity id refers to a redirect'
			);
		} catch ( StorageException $ex ) {
			throw new MessageException(
				'wikibase-wikibaserepopage-storage-exception',
				array( $id->getSerialization(), $ex->getMessage() ),
				'Entity could not be loaded'
			);
		}

		return $entity;
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
	 * @param EntityDocument|null $entity
	 */
	private function setForm( EntityDocument $entity = null ) {
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

		// Form header
		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'post',
					'action' => $this->getPageTitle()->getLocalURL(),
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
		$this->getOutput()->addHTML( $this->getFormElements( $entity ) );

		// Form body
		$submitKey = 'wikibase-' . strtolower( $this->getName() ) . '-submit';
		$this->getOutput()->addHTML(
			Html::input(
				$submitKey,
				$this->msg( $submitKey )->text(),
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
	 * @since 0.5
	 *
	 * @param EntityDocument|null $entity
	 *
	 * @return string HTML
	 */
	protected function getFormElements( EntityDocument $entity = null ) {
		$id = 'wb-modifyentity-id';

		return Html::label(
			$this->msg( 'wikibase-modifyentity-id' )->text(),
			$id,
			array( 'class' => 'wb-label' )
		)
		. Html::input(
			'id',
			$entity === null ? '' : $entity->getId(),
			'text',
			array(
				'class' => 'wb-input',
				'id' => $id
			)
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
	 * @param EntityDocument $entity
	 *
	 * @return Summary|bool
	 */
	abstract protected function modifyEntity( EntityDocument $entity );

	/**
	 * Applies the given ChangeOp to the given Entity.
	 * If validation fails, a ChangeOpValidationException is thrown.
	 *
	 * @since 0.5
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
		$currentEntityRevision = $this->entityRevisionLookup->getEntityRevision( $entity->getId() );
		$result = $changeOp->validate( $currentEntityRevision->getEntity() );

		if ( !$result->isValid() ) {
			throw new ChangeOpValidationException( $result );
		}

		$changeOp->apply( $entity, $summary );
	}

}
