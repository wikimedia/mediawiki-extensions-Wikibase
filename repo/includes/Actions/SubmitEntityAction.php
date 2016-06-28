<?php

namespace Wikibase;

use Content;
use MWException;
use Revision;
use Status;
use Title;
use WatchAction;
use WikiPage;

/**
 * Handles the submit action for Wikibase entities.
 * This performs the undo and restore operations when requested.
 * Otherwise it will just show the normal entity view.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 */
class SubmitEntityAction extends EditEntityAction {

	public function getName() {
		return 'submit';
	}

	public function doesWrites() {
		return true;
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
			$this->showUndoErrorPage( $revisions );
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

		$summary = $request->getText( 'wpSummary' );
		$editToken = $request->getText( 'wpEditToken' );

		if ( $newerRevision->getId() === $latestRevision->getId() ) { // restore
			if ( $diff->isEmpty() ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				$summary = $this->makeRestoreSummary( $olderRevision, $summary );
				$status = $this->attemptSave( $title, $olderContent, $summary, $editToken );
			}
		} else { // undo
			$patchedContent = $latestContent->getPatchedCopy( $diff );

			if ( $patchedContent->equals( $latestContent ) ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				$summary = $this->makeUndoSummary( $newerRevision, $summary );
				$status = $this->attemptSave( $title, $patchedContent, $summary, $editToken );
			}
		}

		if ( $status->isOK() ) {
			$this->getOutput()->redirect( $title->getFullURL() );
		} else {
			$this->showUndoErrorPage( $status );
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
	 * @param string $editToken
	 *
	 * @return Status
	 */
	private function attemptSave( Title $title, Content $content, $summary, $editToken ) {
		$status = $this->getEditTokenStatus( $editToken );

		if ( !$status->isOK() ) {
			return $status;
		}

		$status = $this->getPermissionStatus( 'edit', $title );

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

		$this->doWatch( $title );

		return $status;
	}

	/**
	 * Checks the given permission.
	 *
	 * @param string $permission
	 * @param Title $title
	 * @param string $quick
	 *
	 * @return Status a status object representing the check's result.
	 */
	private function getPermissionStatus( $permission, Title $title, $quick = '' ) {
		//XXX: would be nice to be able to pass the $short flag too,
		//     as used by getUserPermissionsErrorsInternal. But Title doesn't expose that.
		$errors = $title->getUserPermissionsErrors( $permission, $this->getUser(), $quick !== 'quick' );
		$status = Status::newGood();

		foreach ( $errors as $error ) {
			call_user_func_array( array( $status, 'fatal' ), $error );
			$status->setResult( false );
		}

		return $status;
	}

	/**
	 * Checks that the given token is valid.
	 *
	 * @param string $editToken
	 *
	 * @return Status
	 */
	private function getEditTokenStatus( $editToken ) {
		$status = Status::newGood();
		$user = $this->getUser();

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
	 */
	private function doWatch( Title $title ) {
		$user = $this->getUser();

		if ( $user->isLoggedIn()
			&& $user->getOption( 'watchdefault' )
			&& !$user->isWatched( $title )
		) {
			WatchAction::doWatch( $title, $user );
		}
	}

}
