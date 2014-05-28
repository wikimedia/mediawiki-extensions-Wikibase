<?php

namespace Wikibase;

use Status;
use Wikibase\Repo\WikibaseRepo;

/**
 * Handles the submit action for Wikibase entities.
 * This performs the undo and restore operations when requested.
 * Otherwise it will just show the normal entity view.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 */
class SubmitEntityAction extends EditEntityAction {

	public function getName() {
		return 'submit';
	}

	/**
	 * Show the entity using parent::show(), unless an undo operation is requested.
	 * In that case $this->undo(); is called to perform the action after a permission check.
	 */
	public function show() {
		$req = $this->getRequest();

		if ( $req->getCheck('undo') || $req->getCheck('undoafter') || $req->getCheck('restore') ) {
			if ( $this->showPermissionError( "read" ) || $this->showPermissionError( "edit" ) ) {
				return;
			}

			$this->undo();
			return;
		}

		parent::show();
	}

	/**
	 * Perform the undo operation specified by the web request.
	 */
	public function undo() {
		$req = $this->getRequest();

		if ( !$req->wasPosted() || !$req->getCheck('wpSave') ) {
			$args = array( 'action' => "edit" );

			if ( $req->getCheck( 'undo' ) ) {
				$args['undo'] = $req->getInt( 'undo' );
			}

			if ( $req->getCheck( 'undoafter' ) ) {
				$args['undoafter'] = $req->getInt( 'undoafter' );
			}

			if ( $req->getCheck( 'restore' ) ) {
				$args['restore'] = $req->getInt( 'restore' );
			}

			$undoUrl = $this->getTitle()->getLocalURL( $args );
			$this->getOutput()->redirect( $undoUrl );
			return;
		}

		$revisions = $this->loadRevisions();
		if ( !$revisions->isOK() ) {
			$this->showStatusErrorsPage( 'wikibase-undo-revision-error', $revisions );
			return;
		}

		/**
		 * @var \Revision $olderRevision
		 * @var \Revision $newerRevision
		 * @var \Revision $latestRevision
		 */
		list( $olderRevision, $newerRevision, $latestRevision ) = $revisions->getValue();

		/**
		 * @var EntityContent $latestContent
		 * @var EntityContent $olderContent
		 * @var EntityContent $newerContent
		 */
		$olderContent = $olderRevision->getContent();
		$newerContent = $newerRevision->getContent();
		$latestContent = $latestRevision->getContent();

		$diff = $newerContent->getEntity()->getDiff( $olderContent->getEntity() );
		$edit = false;
		$token = $this->getRequest()->getText( 'wpEditToken' );

		//TODO: allow injection/override!
		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$entityRevisionLookup = WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( 'uncached' );
		$entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$entityPermissionChecker = WikibaseRepo::getDefaultInstance()->getEntityPermissionChecker();

		if ( $newerRevision->getId() == $latestRevision->getId() ) { // restore
			$summary = $req->getText( 'wpSummary' );

			if ( $summary === '' ) {
				$summary = $this->makeRestoreSummary( $olderRevision );
			}

			if ( $diff->isEmpty() ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				// make the old content the new content.
				// NOTE: conflict detection is not needed for a plain restore, it's not based on anything.
				$edit = new EditEntity(
					$entityTitleLookup,
					$entityRevisionLookup,
					$entityStore,
					$entityPermissionChecker,
					$olderContent->getEntity(),
					$this->getUser(),
					false,
					$this->getContext() );

				$status = $edit->attemptSave( $summary, 0, $token );
			}
		} else { // undo
			$entity = $latestContent->getEntity()->copy();
			$latestContent->getEntity()->patch( $diff );;

			if ( $latestContent->getEntity()->getDiff( $entity )->isEmpty() ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				$summary = $req->getText( 'wpSummary' );

				if ( $summary === '' ) {
					$summary = $this->makeUndoSummary( $newerRevision );
				}

				//NOTE: use latest revision as base revision - we are saving patched content
				//      based on the latest revision.
				$edit = new EditEntity(
					$entityTitleLookup,
					$entityRevisionLookup,
					$entityStore,
					$entityPermissionChecker,
					$latestContent->getEntity(),
					$this->getUser(),
					$latestRevision->getId(),
					$this->getContext() );

				$status = $edit->attemptSave( $summary, 0, $token );
			}
		}

		if ( $status->isOK() ) {
			$this->getOutput()->redirect( $this->getTitle()->getFullUrl() );
		} else {
			$edit->showErrorPage();
		}
	}

	public function execute() {
		throw new \MWException( "not applicable" );
	}
}