<?php

namespace Wikibase;

use Content;
use Status;
use Title;
use User;
use WatchAction;
use Wikibase\Repo\WikibaseRepo;
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

		$diff = $newerContent->getDiff( $olderContent );

		$title = $this->getTitle();
		$user = $this->getUser();
		$token = $req->getText( 'wpEditToken' );
		$watch = $user->getOption( 'watchdefault' );

		if ( $newerRevision->getId() == $latestRevision->getId() ) { // restore
			$summary = $req->getText( 'wpSummary' );

			if ( $summary === '' ) {
				$summary = $this->makeRestoreSummary( $olderRevision, $newerRevision, $latestRevision );
			}

			if ( $diff->isEmpty() ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				$status = $this->attemptSave( $title, $olderContent, $summary, $user, $token, $watch );
			}
		} else { // undo
			$patchedContent = $latestContent->getPatchedCopy( $diff );

			if ( $patchedContent->equals( $latestContent ) ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				$summary = $req->getText( 'wpSummary' );

				if ( $summary === '' ) {
					$summary = $this->makeUndoSummary( $olderRevision, $newerRevision, $latestRevision );
				}

				$status = $this->attemptSave( $title, $patchedContent, $summary, $user, $token, $watch );
			}
		}

		if ( $status->isOK() ) {
			$this->getOutput()->redirect( $this->getTitle()->getFullUrl() );
		} else {
			$this->showStatusErrorsPage( 'wikibase-undo-title', $status );
		}
	}

	public function execute() {
		throw new \MWException( "not applicable" );
	}

	/**
	 * @param Title $title
	 * @param Content $content
	 * @param string $summary
	 * @param User $user
	 * @param string $token
	 * @param bool $watch
	 *
	 * @return Status
	 */
	private function attemptSave( Title $title, Content $content, $summary, User $user, $token, $watch ) {

		// check token
		$status = $this->getTokenStatus( $user, $token );

		if ( !$status->isOK() ) {
			return $status;
		}

		// check edit permission
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

		// update watchlist
		if ( $watch && $user->isLoggedIn() && !$user->isWatched( $title ) ) {
			WatchAction::doWatch( $title, $user );
		}

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
			call_user_func_array( array( $status, 'fatal'), $error );
			$status->setResult( false );
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Checks that the given token is valid.
	 *
	 * @param User $user
	 * @param string $token
	 *
	 * @return Status
	 */
	private function getTokenStatus( User $user, $token ) {
		$status = Status::newGood();

		if ( !$user->matchEditToken( $token ) ) {
			if ( $user->matchEditTokenNoSuffix( $token ) ) {
				$status = Status::newFatal( 'token_suffix_mismatch' );
			} else {
				$status = Status::newFatal( 'session_fail_preview' );
			}
		}

		return $status;
	}
}