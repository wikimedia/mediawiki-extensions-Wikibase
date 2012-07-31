<?php

namespace Wikibase;
use Content, Html, Linker, Skin, Status, Revision;

/**
 * Handles the edit action for Wikibase entities.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 */
abstract class EditEntityAction extends ViewEntityAction {

	/**
	 * @see Action::getName()
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName() {
		return 'edit';
	}

	public function showPermissionError( $action ) {
		if ( !$this->getTitle()->userCan( $action, $this->getUser() ) ) {

			$this->getOutput()->showPermissionsErrorPage( array( array( "wikibase-cant-undo" ) ), $action ); //TODO: define message
			return true;
		}

		return false;
	}

	/**
	 * @return \Status
	 * @throws \MWException
	 */
	public function loadRevisions( ) {
		$latestRevId = $this->getTitle()->getLatestRevID();

		if ( $latestRevId === 0 ) {
			return Status::newFatal( 'xxxx-no-such-page' );
		}

		$latestRevision = \Revision::newFromId( $latestRevId );

		if ( !$latestRevId ) {
			throw new \MWException( "latest revision not found: $latestRevId" );
		}

		$req = $this->getRequest();

		if ( !$req->getCheck( 'undo' ) ) {
			return Status::newFatal( 'xxxx-incomplete-form' );
		}

		$newerRevision = \Revision::newFromId( $req->getInt( 'undo' ) );

		if ( !$newerRevision ) {
			return Status::newFatal( 'xxxx-revision-not-found', $req->getInt( 'undo' ) );
		}

		if ( $req->getCheck( 'undoafter' ) ) {
			$olderRevision = \Revision::newFromId( $req->getInt( 'undoafter' ) );

			if ( !$olderRevision ) {
				return Status::newFatal( 'xxxx-revision-not-found', $req->getInt( 'undoafter' ) );
			}
		} else {
			$olderRevision = $newerRevision->getPrevious();

			if ( !$olderRevision ) {
				return Status::newFatal( 'xxxx-cant-undo-first-revision' );
			}
		}

		if ( $newerRevision->getPage() != $latestRevision->getPage() ) {
			return Status::newFatal( 'xxxx-revision-belongs-to-different-page', $this->getTitle(), $newerRevision->getId() );
		}

		if ( $olderRevision->getPage() != $latestRevision->getPage() ) {
			return Status::newFatal( 'xxxx-revision-belongs-to-different-page', $this->getTitle(), $newerRevision->getId() );
		}

		return Status::newGood( array(
			$olderRevision, $newerRevision, $latestRevision,
		) );
	}

	/**
	 * Output an error page showing the given status
	 *
	 * @param $title String: message key for page title
	 * @param $status Status: The status to report.
	 *
	 * @todo: would be handy to have this in OutputPage
	 */
	public function showStatusErrorsPage( $title, Status $status ) {
		$this->getOutput()->prepareErrorPage( $this->msg( $title ), $this->msg( 'errorpagetitle' ) );

		$this->getOutput()->addWikiText( $status->getMessage() );

		$this->getOutput()->returnToMain();
	}

	/**
	 * @see FormlessAction::show
	 *
	 * @since 0.1
	 */
	public function show() {
		if ( $this->showPermissionError( "read" ) || $this->showPermissionError( "edit" ) ) {
			return;
		}

		$revisions = $this->loadRevisions();
		if ( !$revisions->isOK() ) {
			$this->showStatusErrorsPage( 'wikibase-undo-revision-error', $revisions ); //TODO: define message
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


		//$this->getOutput()->setContext( $this->getContext() ); //XXX: WTF?
		$this->getOutput()->setPageTitle(
			$this->msg(
				'wikibase-undo-title', //TODO: define message
				$this->getLabelText( $latestContent ),
				$olderRevision->getId(),
				$newerRevision->getId()
			)
		);

		$diff = $olderContent->getEntity()->getDiff( $newerContent->getEntity() );
		$appDiff = $diff->getApplicableDiff( $latestContent->getEntity()->toArray() );

		if ( !$appDiff->isEmpty() ) {
			$this->getOutput()->addWikiMsg( 'undo-success' );
		}

		$omitted = count( $diff->getOperations() ) - count( $appDiff->getOperations() );
		$this->getOutput()->addWikiMsg( 'wikibase-omitted-undo-ops', $omitted ); //TODO: define message

		if ( $appDiff->isEmpty() ) {
			$this->getOutput()->addWikiMsg( 'wikibase-empty-undo' ); //TODO: define message
			return;
		}

		$this->displayUndoDiff( $appDiff );

		$autoSummary = $this->makeAutoSummary( $olderRevision, $newerRevision, $latestRevision );
		$this->showStandardInputs( $autoSummary );
	}

	/**
	 * @return String
	 */
	public function getLabelText( EntityContent $content ) {

		$langCode = $this->getContext()->getLanguage()->getCode();
		list( $labelCode, $labelText, $labelLang) =
			Utils::lookupUserMultilangText(
				$content->getEntity()->getLabels(),
				Utils::languageChain( $langCode ),
				array( $langCode, $this->getPageTitle(), $this->getContext()->getLanguage() )
			);

		return $labelText;
	}

	/**
	 * @return String
	 */
	public function makeAutoSummary( Revision $olderRevision, Revision $newerRevision, Revision $latestRevision ) {
		$autoSummary = $this->msg(
			'undo-summary',
			$newerRevision->getId(),
			$newerRevision->getUserText()
		);

		return $autoSummary;
	}

	/**
	 * @return string
	 */
	public function getCancelLink() {
		$cancelParams = array();

		return Linker::linkKnown(
			$this->getContext()->getTitle(),
			wfMsgExt( 'cancel', array( 'parseinline' ) ),
			array( 'id' => 'mw-editform-cancel' ),
			$cancelParams
		);
	}

	/**
	 * Add style sheets and supporting JS for diff display.
	 *
	 * @since 0.1
	 *
	 */
	function showDiffStyle() {
		$this->getOutput()->addModuleStyles( 'mediawiki.action.history.diff' );
	}

	/**
	 * Standard summary input and label (wgSummary), abstracted so EditPage
	 * subclasses may reorganize the form.
	 * Note that you do not need to worry about the label's for=, it will be
	 * inferred by the id given to the input. You can remove them both by
	 * passing array( 'id' => false ) to $userInputAttrs.
	 *
	 * @param $summary string The value of the summary input
	 * @param $labelText string The html to place inside the label
	 * @param $inputAttrs array of attrs to use on the input
	 * @param $spanLabelAttrs array of attrs to use on the span inside the label
	 *
	 * @return array An array in the format array( $label, $input )
	 */
	function getSummaryInput( $summary = "", $labelText = null, $inputAttrs = null, $spanLabelAttrs = null ) {
		// Note: the maxlength is overriden in JS to 255 and to make it use UTF-8 bytes, not characters.
		$inputAttrs = ( is_array( $inputAttrs ) ? $inputAttrs : array() ) + array(
			'id' => 'wpSummary',
			'maxlength' => '200',
			'tabindex' => '1',
			'size' => 60,
			'spellcheck' => 'true',
		) + Linker::tooltipAndAccesskeyAttribs( 'summary' );

		$spanLabelAttrs = ( is_array( $spanLabelAttrs ) ? $spanLabelAttrs : array() ) + array(
			'class' => 'mw-summary',
			'id' => "wpSummaryLabel"
		);

		$label = null;
		if ( $labelText ) {
			$label = Html::element( 'label', $inputAttrs['id'] ? array( 'for' => $inputAttrs['id'] ) : null, $labelText );
			$label = Html::element( 'span', $spanLabelAttrs, $label );
		}

		$input = Html::input( 'wpSummary', $summary, 'text', $inputAttrs );

		return array( $label, $input );
	}

	/**
	 * Displays the undo diff.
	 *
	 * @since 0.1
	 *
	 * @param EntityDiff $diff
	 */
	protected function displayUndoDiff( EntityDiff $diff ) {
		$diffView = $diff->getView();
		$diffView->setContext( $this->getContext() );
		$this->getOutput()->addHTML( Html::rawElement( 'table', array(),  '<colgroup><col class="diff-marker"> <col class="diff-content"><col class="diff-marker"> <col class="diff-content"></colgroup><tbody>' . $diffView->getHtml() . '</tbody>') );
		$this->showDiffStyle();
	}

	/**
	 * Returns an array of html code of the following buttons:
	 * save, diff, preview and live
	 *
	 * @param $tabindex int Current tabindex
	 *
	 * @return array
	 */
	public function getEditButtons( &$tabindex ) {
		$buttons = array();

		$temp = array(
			'id'        => 'wpSave',
			'name'      => 'wpSave',
			'type'      => 'submit',
			'tabindex'  => ++$tabindex,
			'value'     => wfMsg( 'savearticle' ),
			'accesskey' => wfMsg( 'accesskey-save' ),
			'title'     => wfMsg( 'tooltip-save' ) . ' [' . wfMsg( 'accesskey-save' ) . ']',
		);
		$buttons['save'] = Html::element( 'input', $temp, '' );

		++$tabindex; // use the same for preview and live preview
		return $buttons;
	}

	protected function showStandardInputs ( $summary = '', &$tabindex = 2 ) {
		$req = $this->getRequest();

		$actionUrl = $this->getTitle()->getLocalURL(
			array(
				'action' => "submit",
				'undo' => $req->getInt( 'undo' ),
				'undoafter' => $req->getInt( 'undoafter' ),
			)
		);

		$this->getOutput()->addHTML( Html::openElement( 'form', array( 'id' =>"undo", 'name' => "undo",
			'method' => 'post',
			'action' => $actionUrl,
			'enctype' => 'multipart/form-data' ) ) );

		$this->getOutput()->addHTML( "<div class='editOptions'>\n" );

		$labelText = wfMsgExt( 'summary', 'parseinline' );
		list( $label, $field ) = $this->getSummaryInput( $summary, $labelText );
		$this->getOutput()->addHTML( $label . " " . $field );
		$this->getOutput()->addHTML( "<div class='editButtons'>\n" );
		$this->getOutput()->addHTML( implode( $this->getEditButtons( $tabindex ), "\n" ) . "\n" );

		$cancel = $this->getCancelLink();
		if ( $cancel !== '' ) {
			$cancel .= wfMsgExt( 'pipe-separator' , 'escapenoentities' );
		}

		$this->getOutput()->addHTML( "</div><!-- editButtons -->\n</div><!-- editOptions -->\n" );

		$this->getOutput()->addHTML( "\n" . Html::hidden( "wpEditToken", $this->getUser()->getEditToken() ) . "\n" );

		$this->getOutput()->addHTML( Html::closeElement( 'form' ) );
	}
}


class SubmitEntityAction extends EditEntityAction {

	public function getName() {
		return 'submit';
	}

	/**
	 * Make sure the form isn't faking a user's credentials.
	 *
	 * @param $request \WebRequest
	 * @return bool
	 * @private
	 */
	function tokenOk( \WebRequest &$request ) {
		$token = $request->getVal( 'wpEditToken' );
		$tokenOk = $this->getUser()->matchEditToken( $token );
		$tokenOkExceptSuffix = $this->getUser()->matchEditTokenNoSuffix( $token );
		return array( $tokenOk, $tokenOkExceptSuffix );
	}

	public function show() {
		if ( $this->showPermissionError( "read" ) || $this->showPermissionError( "edit" ) ) {
			return;
		}

		$req = $this->getRequest();

		if ( !$req->getCheck('undo') ) {
			$this->getOutput()->redirect( $this->getTitle()->getFullUrl() );
		}

		if ( !$req->wasPosted() || !$req->getCheck('wpSave') ) {
			$undoUrl = $this->getTitle()->getLocalURL(
				array(
					'action' => "submit",
					'undo' => $req->getInt( 'undo' ),
					'undoafter' => $req->getInt( 'undoafter' ),
				)
			);

			$this->getOutput()->redirect( $undoUrl );
		}

		list( $tokenOk, $tokenOkExceptSuffix ) = $this->tokenOk( $req );

		if ( !$tokenOk ) {
			if ( $tokenOkExceptSuffix ) {
				$this->getOutput()->addWikiMsg( 'token_suffix_mismatch' ); //TODO: check message
			} else {
				$this->getOutput()->addWikiMsg( 'session_fail_preview' ); //TODO: check message
			}
		}

		$revisions = $this->loadRevisions();
		if ( !$revisions->isOK() ) {
			$this->showStatusErrorsPage( 'wikibase-undo-revision-error', $revisions ); //TODO: define message
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

		$diff = $olderContent->getEntity()->getDiff( $newerContent->getEntity() );

		$appDiff = $diff->getApplicableDiff( $latestContent->getEntity()->toArray() );

		if ( !$appDiff->isEmpty() ) {
			$entity = $latestContent->getEntity();
			$appDiff->apply( $entity );

			$summary = $req->getText( 'wpSummary' );
			if ( $summary === '' ) {
				$summary = $this->makeAutoSummary( $olderRevision, $newerRevision, $latestRevision );
			}

			$status = $latestContent->save( $summary, $this->getUser() );

			if ( $status->isOK() ) {
				$this->getOutput()->redirect( $this->getTitle()->getFullUrl() );
			} else {
				$this->showStatusErrorsPage( 'xxxxx-undo-failed', $status ); //TODO: title message
			}
		}

	}

	public function execute() {
		throw new \MWException( "not applicable" );
	}
}