<?php

namespace Wikibase;

use Content;
use IContextSource;
use MWException;
use Page;
use Revision;
use Status;
use Title;
use WatchAction;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Handles the submit action for Wikibase entities.
 * This performs the undo and restore operations when requested.
 * Otherwise it will just show the normal entity view.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 */
class SubmitEntityAction extends EditEntityAction {

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @see EditEntityAction::__construct
	 *
	 * @param Page $page
	 * @param IContextSource|null $context
	 */
	public function __construct( Page $page, IContextSource $context = null ) {
		parent::__construct( $page, $context );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->summaryFormatter = $wikibaseRepo->getSummaryFormatter();
	}

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
		$undidRevId = $request->getInt( 'undo' );
		$title = $this->getTitle();

		if ( !$request->wasPosted() || !$request->getCheck( 'wpSave' ) ) {
			$args = [ 'action' => 'edit' ];

			if ( $undidRevId !== 0 ) {
				$args['undo'] = $undidRevId;
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
		$patchedContent = $this->getPatchContent( $olderRevision, $newerRevision, $latestRevision );
		$latestContent = $latestRevision->getContent();

		if ( $patchedContent->equals( $latestContent ) ) {
			$status = Status::newGood();
			$status->warning( 'wikibase-empty-undo' );
		} else {
			$summary = $request->getText( 'wpSummary' );

			if ( $request->getCheck( 'restore' ) ) {
				$summary = $this->makeSummary( 'restore', $olderRevision, $summary );
			} else {
				$summary = $this->makeSummary( 'undo', $newerRevision, $summary );
			}

			$editToken = $request->getText( 'wpEditToken' );
			$status = $this->attemptSave( $title, $patchedContent, $summary, $undidRevId, $editToken );
		}

		if ( $status->isOK() ) {
			$this->getOutput()->redirect( $title->getFullURL() );
		} else {
			$this->showUndoErrorPage( $status );
		}
	}

	/**
	 * @param Revision $olderRevision
	 * @param Revision $newerRevision
	 * @param Revision $latestRevision
	 *
	 * @return EntityContent
	 */
	private function getPatchContent(
		Revision $olderRevision,
		Revision $newerRevision,
		Revision $latestRevision
	) {
		/**
		 * @var EntityContent $olderContent
		 * @var EntityContent $newerContent
		 * @var EntityContent $latestContent
		 */
		$olderContent = $olderRevision->getContent();
		$newerContent = $newerRevision->getContent();
		$latestContent = $latestRevision->getContent();

		return $latestContent->getPatchedCopy( $newerContent->getDiff( $olderContent ) );
	}

	/**
	 * @param string $actionName
	 * @param Revision $revision
	 * @param string $userSummary
	 *
	 * @return string
	 */
	private function makeSummary( $actionName, Revision $revision, $userSummary ) {
		$summary = new Summary();
		$summary->setAction( $actionName );
		$summary->addAutoCommentArgs( $revision->getId(), $revision->getUserText() );
		$summary->setUserSummary( $userSummary );

		return $this->summaryFormatter->formatSummary( $summary );
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
	 * @param int $undidRevId
	 * @param string $editToken
	 *
	 * @return Status
	 */
	private function attemptSave( Title $title, Content $content, $summary, $undidRevId, $editToken ) {
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
		$status = $page->doEditContent(
			$content,
			$summary,
			0,
			false,
			$this->getUser(),
			null,
			[],
			$undidRevId
		);

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
			call_user_func_array( [ $status, 'fatal' ], $error );
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
