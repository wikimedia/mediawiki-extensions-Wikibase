<?php

namespace Wikibase;

use Content;
use MWException;
use Revision;
use Status;
use Title;
use User;
use WatchAction;
use WikiPage;

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
		$request = $this->getRequest();

		if ( $request->getCheck( 'undo' ) || $request->getCheck( 'undoafter' ) || $request->getCheck( 'restore' ) ) {
			if ( $this->showPermissionError( 'read' ) || $this->showPermissionError( 'edit' ) ) {
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
		$request = $this->getRequest();
		$title = $this->getTitle();

		if ( !$request->wasPosted() || !$request->getCheck( 'wpSave' ) ) {
			$args = array( 'action' => 'edit' );

			if ( $request->getCheck( 'undo' ) ) {
				$args['undo'] = $request->getInt( 'undo' );
			}

			if ( $request->getCheck( 'undoafter' ) ) {
				$args['undoafter'] = $request->getInt( 'undoafter' );
			}

			if ( $request->getCheck( 'restore' ) ) {
				$args['restore'] = $request->getInt( 'restore' );
			}

			$undoUrl = $title->getLocalURL( $args );
			$this->getOutput()->redirect( $undoUrl );
			return;
		}

		$revisions = $this->loadRevisions();
		if ( !$revisions->isOK() ) {
			$this->showStatusErrorsPage( 'wikibase-undo-revision-error', $revisions );
			return;
		}

		/**
		 * @var Revision $olderRevision
		 * @var Revision $newerRevision
		 * @var Revision $latestRevision
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

		$diff = $newerContent->getDiff( $olderContent );

		$user = $this->getUser();
		$summary = $request->getText( 'wpSummary' );
		$editToken = $request->getText( 'wpEditToken' );

		if ( $newerRevision->getId() === $latestRevision->getId() ) { // restore
			if ( $summary === '' ) {
				$summary = $this->makeRestoreSummary( $olderRevision );
			}

			if ( $diff->isEmpty() ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				$status = $this->attemptSave( $title, $olderContent, $summary, $user, $editToken );
			}
		} else { // undo
			$patchedContent = $latestContent->getPatchedCopy( $diff );

			if ( $patchedContent->equals( $latestContent ) ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				if ( $summary === '' ) {
					$summary = $this->makeUndoSummary( $newerRevision );
				}

				$status = $this->attemptSave( $title, $patchedContent, $summary, $user, $editToken );
			}
		}

		if ( $status->isOK() ) {
			$this->getOutput()->redirect( $title->getFullUrl() );
		} else {
			$this->showStatusErrorsPage( 'wikibase-undo-title', $status );
		}
	}

	/**
	 * @throws MWException
	 */
	public function execute() {
		throw new MWException( 'Not applicable.' );
	}

	/**
	 * @param Title $title
	 * @param Content $content
	 * @param string $summary
	 * @param User $user
	 * @param string $editToken
	 *
	 * @return Status
	 */
	private function attemptSave( Title $title, Content $content, $summary, User $user, $editToken ) {
		$status = $this->getEditTokenStatus( $user, $editToken );

		if ( !$status->isOK() ) {
			return $status;
		}

		$status = $this->getPermissionStatus( $user, 'edit', $title );

		if ( !$status->isOK() ) {
			return $status;
		}

		// save edit
		$page = new WikiPage( $title );

		// NOTE: Constraint checks are performed automatically via EntityContent::prepareSave.
		$status = $page->doEditContent( $content, $summary );

		if ( !$status->isOK() ) {
			return $status;
		}

		$this->doWatch( $title, $user );

		return $status;
	}

	/**
	 * Checks the given permission.
	 *
	 * @param User $user
	 * @param string $permission
	 * @param Title $title
	 * @param string $quick
	 *
	 * @return Status a status object representing the check's result.
	 */
	private function getPermissionStatus( User $user, $permission, Title $title, $quick = '' ) {
		wfProfileIn( __METHOD__ );

		//XXX: would be nice to be able to pass the $short flag too,
		//     as used by getUserPermissionsErrorsInternal. But Title doesn't expose that.
		$errors = $title->getUserPermissionsErrors( $permission, $user, $quick !== 'quick' );
		$status = Status::newGood();

		foreach ( $errors as $error ) {
			call_user_func_array( array( $status, 'fatal' ), $error );
			$status->setResult( false );
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Checks that the given token is valid.
	 *
	 * @param User $user
	 * @param string $editToken
	 *
	 * @return Status
	 */
	private function getEditTokenStatus( User $user, $editToken ) {
		$status = Status::newGood();

		if ( !$user->matchEditToken( $editToken ) ) {
			if ( $user->matchEditTokenNoSuffix( $editToken ) ) {
				$status = Status::newFatal( 'token_suffix_mismatch' );
			} else {
				$status = Status::newFatal( 'session_fail_preview' );
			}
		}

		return $status;
	}

	/**
	 * Update watchlist.
	 *
	 * @param Title $title
	 * @param User $user
	 */
	private function doWatch( Title $title, User $user ) {
		if ( $user->isLoggedIn()
			&& $user->getOption( 'watchdefault' )
			&& !$user->isWatched( $title )
		) {
			WatchAction::doWatch( $title, $user );
		}
	}

}
