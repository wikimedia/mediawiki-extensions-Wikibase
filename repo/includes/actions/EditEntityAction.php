<?php

namespace Wikibase;
use Diff\CallbackListDiffer;
use  Html, Linker, Skin, Status, Revision;
use Wikibase\Repo\WikibaseRepo;

/**
 * @file
 * @ingroup WikibaseRepo
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 */

/**
 * Handles the edit action for Wikibase entities.
 * This shows the forms for the undo and restore operations if requested.
 * Otherwise it will just show the normal entity view.
 *
 * @since 0.1
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

	/**
	 * Show an error page if the user is not allowed to perform the given action.
	 *
	 * @since 0.1
	 *
	 * @param String $action the action to check
	 *
	 * @return bool true if there were permission errors
	 */
	public function showPermissionError( $action ) {
		if ( !$this->getTitle()->userCan( $action, $this->getUser() ) ) {

			$this->getOutput()->showPermissionsErrorPage(
				array( $this->getTitle()->getUserPermissionsErrors( $action, $this->getUser() ) ),
				$action
			);

			return true;
		}

		return false;
	}

	/**
	 * Loads the revisions specified by the web request and returns them as a 3 element array wrapped in a Status object.
	 * If any error arises, it will be reported using the status object.
	 *
	 * @since 0.1
	 *
	 * @return \Status a Status object containing an array with three revisions, ($olderRevision, $newerRevision, $latestRevision)
	 * @throws \MWException if the page's latest revision can not be loaded
	 */
	public function loadRevisions( ) {
		$latestRevId = $this->getTitle()->getLatestRevID();

		if ( $latestRevId === 0 ) {
			return Status::newFatal( 'missing-article', $this->getTitle()->getPrefixedText(), '' ); //XXX: better message
		}

		$latestRevision = \Revision::newFromId( $latestRevId );

		if ( !$latestRevId ) {
			throw new \MWException( "latest revision not found: $latestRevId" );
		}

		$req = $this->getRequest();

		if ( $req->getCheck( 'restore' ) ) { // nearly the same as undoafter without undo
			$olderRevision = \Revision::newFromId( $req->getInt( 'restore' ) );

			if ( !$olderRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'restore' ) );
			}

			// ignore undo, even if set
			$newerRevision = $latestRevision;
		} else if ( $req->getCheck( 'undo' ) ) {
			$newerRevision = \Revision::newFromId( $req->getInt( 'undo' ) );

			if ( !$newerRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'undo' ) );
			}

			if ( $req->getCheck( 'undoafter' ) ) {
				$olderRevision = \Revision::newFromId( $req->getInt( 'undoafter' ) );

				if ( !$olderRevision ) {
					return Status::newFatal( 'undo-norev', $req->getInt( 'undoafter' ) );
				}
			} else {
				$olderRevision = $newerRevision->getPrevious();

				if ( !$olderRevision ) {
					return Status::newFatal( 'wikibase-undo-firstrev' );
				}
			}
		} else if ( $req->getCheck( 'undoafter' ) ) {
			$olderRevision = \Revision::newFromId( $req->getInt( 'undoafter' ) );

			if ( !$olderRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'undo' ) );
			}

			// we already know that undo is not set
			$newerRevision = $latestRevision;
		} else {
			return Status::newFatal( 'edit_form_incomplete' ); //XXX: better message?
		}

		if ( $olderRevision->getId() == $newerRevision->getId() ) {
			return Status::newFatal( 'wikibase-undo-samerev', $this->getTitle() );
		}

		if ( $newerRevision->getPage() != $latestRevision->getPage() ) {
			return Status::newFatal( 'wikibase-undo-badpage', $this->getTitle(), $newerRevision->getId() );
		}

		if ( $olderRevision->getPage() != $latestRevision->getPage() ) {
			return Status::newFatal( 'wikibase-undo-badpage', $this->getTitle(), $olderRevision->getId() );
		}

		if ( $olderRevision->getContent() === null ) {
			return Status::newFatal( 'wikibase-undo-nocontent', $this->getTitle(), $olderRevision->getId() );
		}

		if ( $newerRevision->getContent() === null ) {
			return Status::newFatal( 'wikibase-undo-nocontent', $this->getTitle(), $newerRevision->getId() );
		}

		if ( $latestRevision->getContent() === null ) {
			return Status::newFatal( 'wikibase-undo-nocontent', $this->getTitle(), $latestRevision->getId() );
		}

		return Status::newGood( array(
			$olderRevision, $newerRevision, $latestRevision,
		) );
	}

	/**
	 * Output an error page showing the given status
	 *
	 * @since 0.1
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
	 * Calls paren't show() action to just display the entity, unless an undo action is requested.
	 *
	 * @since 0.1
	 */
	public function show() {
		$req = $this->getRequest();

		if ( $req->getCheck( 'undo' ) || $req->getCheck( 'undoafter' ) || $req->getCheck( 'restore' ) ) {
			$this->showUndoForm();
		} else {
			parent::show();
		}
	}

	/**
	 * Show an undo form
	 *
	 * @since 0.1
	 */
	public function showUndoForm() {
		$req = $this->getRequest();

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

		$restore = $req->getCheck( 'restore' );

		//$this->getOutput()->setContext( $this->getContext() ); //XXX: WTF?
		$this->getOutput()->setPageTitle(
			$this->msg(
				$restore ? 'wikibase-restore-title' : 'wikibase-undo-title',
				$this->getLabelText( $latestContent ),
				$olderRevision->getId(),
				$newerRevision->getId()
			)
		);

		// diff from newer to older
		$diff = $newerContent->getEntity()->getDiff( $olderContent->getEntity() );

		if ( $newerRevision->getId() == $latestRevision->getId() ) {
			// if the revision to undo is the latest revision, then there can be no conflicts
			$appDiff = $diff;
		} else {
			$patchedCurrent = clone $latestContent->getEntity();
			$patchedCurrent->patch( $diff );
			$appDiff = $latestContent->getEntity()->getDiff( $patchedCurrent );
		}

		if ( !$restore ) {
			$omitted = $diff->count() - $appDiff->count();

			if ( !$appDiff->isEmpty() ) {
				$this->getOutput()->addHTML( Html::openElement("p") );
				$this->getOutput()->addWikiMsg( $omitted > 0 ? 'wikibase-partial-undo' : 'undo-success' );
				$this->getOutput()->addHTML( Html::closeElement("p") );
			}

			if ( $omitted > 0 ) {
				$this->getOutput()->addHTML( Html::openElement("p") );
				$this->getOutput()->addWikiMsg( 'wikibase-omitted-undo-ops', $omitted );
				$this->getOutput()->addHTML( Html::closeElement("p") );
			}
		}

		if ( $appDiff->isEmpty() ) {
			$this->getOutput()->addHTML( Html::openElement("p") );
			$this->getOutput()->addWikiMsg( 'wikibase-empty-undo' );
			$this->getOutput()->addHTML( Html::closeElement("p") );
			return;
		}

		$this->displayUndoDiff( $appDiff );

		$autoSummary = $restore ? $this->makeRestoreSummary( $olderRevision, $newerRevision, $latestRevision )
								: $this->makeUndoSummary( $olderRevision, $newerRevision, $latestRevision );

		$this->showConfirmationForm( $autoSummary );
	}

	/**
	 * Returns the label that should be shown to represent the given entity.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $content
	 *
	 * @return String
	 */
	public function getLabelText( EntityContent $content ) {

		$languageFallbackChain = $this->getLanguageFallbackChain();
		$labelData = $languageFallbackChain->extractPreferredValueOrAny( $content->getEntity()->getLabels() );

		if ( $labelData ) {
			return $labelData['value'];
		} else {
			return $this->getPageTitle();
		}
	}

	/**
	 * Returns an edit summary representing a restore-operation defined by the three given revisions.
	 *
	 * @since 0.1
	 *
	 * @param \Revision $olderRevision
	 * @param \Revision $newerRevision
	 * @param \Revision $latestRevision
	 *
	 * @return String
	 */
	public function makeRestoreSummary( Revision $olderRevision, Revision $newerRevision, Revision $latestRevision ) {
		$autoSummary = wfMessage( //TODO: use translatable auto-comment!
			'wikibase-restore-summary',
			$olderRevision->getId(),
			$olderRevision->getUserText()
		)->inContentLanguage()->text();

		return $autoSummary;
	}

	/**
	 * Returns an edit summary representing an undo-operation defined by the three given revisions.
	 *
	 * @since 0.1
	 *
	 * @param \Revision $olderRevision
	 * @param \Revision $newerRevision
	 * @param \Revision $latestRevision
	 *
	 * @return String
	 */
	public function makeUndoSummary( Revision $olderRevision, Revision $newerRevision, Revision $latestRevision ) {
		$autoSummary = wfMessage( //TODO: use translatable auto-comment!
			'undo-summary',
			$newerRevision->getId(),
			$newerRevision->getUserText()
		)->inContentLanguage()->text();

		return $autoSummary;
	}

	/**
	 * Returns a cancel link back to viewing the entity's page
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getCancelLink() {
		$cancelParams = array();

		return Linker::linkKnown(
			$this->getContext()->getTitle(),
			wfMessage( 'cancel' )->parse(),
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
	public function showDiffStyle() {
		$this->getOutput()->addModuleStyles( 'mediawiki.action.history.diff' );
	}

	/**
	 * Generate standard summary input and label (wgSummary), compatible to EditPage.
	 *
	 * @since 0.1
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
			$label = Html::rawElement( 'span', $spanLabelAttrs, $label );
		}

		$input = Html::input( 'wpSummary', htmlspecialchars( $summary ), 'text', $inputAttrs );

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
		$tableClass = 'diff diff-contentalign-' . htmlspecialchars( $this->getTitle()->getPageLanguage()->alignStart() );

		$this->getOutput()->addHTML( Html::openElement( 'table', array( 'class' => $tableClass ) ) );

		$this->getOutput()->addHTML( '<colgroup><col class="diff-marker"> <col class="diff-content"><col class="diff-marker"> <col class="diff-content"></colgroup>' );
		$this->getOutput()->addHTML( Html::openElement( 'tbody' ) );

		$old = $this->msg( 'currentrev' )->parse();
		$new = $this->msg( 'yourtext' )->parse(); //XXX: better message?

		$this->getOutput()->addHTML( Html::openElement( 'tr', array( 'valign' => 'top' ) ) );
		$this->getOutput()->addHTML( Html::rawElement( 'td', array( 'colspan' => '2' ), Html::rawElement( 'div', array( 'id' => 'mw-diff-otitle1' ), $old ) ) );
		$this->getOutput()->addHTML( Html::rawElement( 'td', array( 'colspan' => '2' ), Html::rawElement( 'div', array( 'id' => 'mw-diff-ntitle1' ), $new ) ) );
		$this->getOutput()->addHTML( Html::closeElement( 'tr' ) );

		$langCode = $this->getContext()->getLanguage()->getCode();

		$comparer = function( \Comparable $old, \Comparable $new ) {
			return $old->equals( $new );
		};

		// TODO: derp inject the EntityDiffVisualizer
		$diffVisualizer = new EntityDiffVisualizer(
			$this->getContext(),
			new ClaimDiffer( new CallbackListDiffer( $comparer ) ),
			new ClaimDifferenceVisualizer(
				new WikiPageEntityLookup(),
				$langCode,
				WikibaseRepo::getDefaultInstance()->getIdFormatter()
			)
		);

		$this->getOutput()->addHTML( $diffVisualizer->visualizeDiff( $diff ) );

		$this->getOutput()->addHTML( Html::closeElement( 'tbody' ) );
		$this->getOutput()->addHTML( Html::closeElement( 'table' ) );

		$this->showDiffStyle();
	}

	/**
	 * Returns an array of html code of the following buttons:
	 * save, diff, preview and live
	 *
	 * @since 0.1
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
			'value'     => $this->msg( 'savearticle' )->text(),
			'accesskey' => $this->msg( 'accesskey-save' )->text(),
			'title'     => $this->msg( 'tooltip-save' )->text() . ' [' . $this->msg( 'accesskey-save' )->text() . ']',
		);
		$buttons['save'] = Html::element( 'input', $temp, '' );

		++$tabindex; // use the same for preview and live preview
		return $buttons;
	}

	/**
	 * Shows a form that can be used to confirm the requested undo/restore action.
	 *
	 * @since 0.1
	 *
	 * @param string $summary
	 * @param int    $tabindex
	 */
	protected function showConfirmationForm( $summary = '', &$tabindex = 2 ) {
		$req = $this->getRequest();

		$args = array(
			'action' => "submit",
		);

		if ( $req->getInt( 'undo' ) )  {
			$args[ 'undo' ] = $req->getInt( 'undo' );
		}

		if ( $req->getInt( 'undoafter' ) )  {
			$args[ 'undoafter' ] = $req->getInt( 'undoafter' );
		}

		if ( $req->getInt( 'restore' ) )  {
			$args[ 'restore' ] = $req->getInt( 'restore' );
		}

		$actionUrl = $this->getTitle()->getLocalURL( $args );

		$this->getOutput()->addHTML( Html::openElement( 'div', array( 'style' =>"margin-top: 1em") ) );

		$this->getOutput()->addHTML( Html::openElement( 'form', array(
			'id' =>"undo",
			'name' => "undo",
			'method' => 'post',
			'action' => $actionUrl,
			'enctype' => 'multipart/form-data' ) ) );

		$this->getOutput()->addHTML( "<p class='editOptions'>\n" );

		$labelText = wfMessage( 'summary' )->parse();
		list( $label, $field ) = $this->getSummaryInput( $summary, $labelText );
		$this->getOutput()->addHTML( $label . " " . $field );
		$this->getOutput()->addHTML( "<p class='editButtons'>\n" );
		$this->getOutput()->addHTML( implode( $this->getEditButtons( $tabindex ), "\n" ) . "\n" );

		$cancel = $this->getCancelLink();
		if ( $cancel !== '' ) {
			$this->getOutput()->addHTML( wfMessage( 'pipe-separator' )->escaped() );
			$this->getOutput()->addHTML( $cancel );
		}

		$this->getOutput()->addHTML( "</p><!-- editButtons -->\n</p><!-- editOptions -->\n" );

		$this->getOutput()->addHTML( "\n" . Html::hidden( "wpEditToken", $this->getUser()->getEditToken() ) . "\n" );
		$this->getOutput()->addHTML( "\n" . Html::hidden( "wpBaseRev", $this->getTitle()->getLatestRevID() ) . "\n" );

		$this->getOutput()->addHTML( Html::closeElement( 'form' ) );
		$this->getOutput()->addHTML( Html::closeElement( 'div' ) );
	}
}


/**
 * Handles the submit action for Wikibase entities.
 * This performs the undo and restore operations when requested.
 * Otherwise it will just show the normal entity view.
 *
 * @since 0.1
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

		if ( $newerRevision->getId() == $latestRevision->getId() ) { // restore
			$summary = $req->getText( 'wpSummary' );

			if ( $summary === '' ) {
				$summary = $this->makeRestoreSummary( $olderRevision, $newerRevision, $latestRevision );
			}

			if ( $diff->isEmpty() ) {
				$status = Status::newGood();
				$status->warning( 'wikibase-empty-undo' );
			} else {
				// make the old content the new content.
				// NOTE: conflict detection is not needed for a plain restore, it's not based on anything.
				$edit = new EditEntity( $olderContent, $this->getUser(), false, $this->getContext() );
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
					$summary = $this->makeUndoSummary( $olderRevision, $newerRevision, $latestRevision );
				}

				//NOTE: use latest revision as base revision - we are saving patched content
				//      based on the latest revision.
				$edit = new EditEntity( $latestContent, $this->getUser(), $latestRevision->getId(), $this->getContext() );
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
