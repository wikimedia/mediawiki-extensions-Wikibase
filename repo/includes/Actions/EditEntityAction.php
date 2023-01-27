<?php

namespace Wikibase\Repo\Actions;

use Article;
use Html;
use IContextSource;
use Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MWException;
use OOUI\ButtonInputWidget;
use OOUI\ButtonWidget;
use OOUI\FieldLayout;
use OOUI\HtmlSnippet;
use OOUI\TextInputWidget;
use Status;
use WebRequest;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\DispatchingEntityDiffVisualizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * Handles the edit action for Wikibase entities.
 * This shows the forms for the undo and restore operations if requested.
 * Otherwise it will just show the normal entity view.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 */
class EditEntityAction extends ViewEntityAction {

	/**
	 * @var BasicEntityDiffVisualizer
	 */
	private $entityDiffVisualizer;

	/**
	 * @see Action::__construct
	 *
	 * @param Article $article
	 * @param IContextSource $context
	 */
	public function __construct( Article $article, IContextSource $context ) {
		parent::__construct( $article, $context );

		$this->entityDiffVisualizer = new DispatchingEntityDiffVisualizer(
			WikibaseRepo::getEntityDiffVisualizerFactory(),
			$this->getContext()
		);
	}

	/**
	 * @see Action::getName()
	 *
	 * @return string
	 */
	public function getName() {
		return 'edit';
	}

	/**
	 * Show an error page if the user is not allowed to perform the given action.
	 *
	 * @param string $action The action to check
	 *
	 * @return bool true if there were permission errors
	 */
	protected function showPermissionError( $action ) {
		$rigor = $this->getRequest()->wasPosted() ? 'secure' : 'full';
		$pm = MediaWikiServices::getInstance()->getPermissionManager();
		if ( !$pm->userCan( $action, $this->getUser(), $this->getTitle(), $rigor ) ) {
			$this->getOutput()->showPermissionsErrorPage(
				$pm->getPermissionErrors( $action, $this->getUser(), $this->getTitle(), $rigor ),
				$action
			);

			return true;
		}

		return false;
	}

	/**
	 * Loads the revisions specified by the web request and returns them as a three element array
	 * wrapped in a Status object. If any error arises, it will be reported using the status object.
	 *
	 * @return Status A Status object containing an array with three revision record objects,
	 *   [ $olderRevision, $newerRevision, $latestRevision ].
	 * @throws MWException if the page's latest revision cannot be loaded
	 */
	protected function loadRevisions() {
		$latestRevId = $this->getTitle()->getLatestRevID();

		if ( $latestRevId === 0 ) {
			// XXX: Better message
			return Status::newFatal( 'missing-article', $this->getTitle()->getPrefixedText(), '' );
		}

		$latestRevision = MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getRevisionById( $latestRevId );

		if ( !$latestRevId ) {
			throw new MWException( "latest revision not found: $latestRevId" );
		}

		return $this->getStatus( $this->getRequest(), $latestRevision );
	}

	/**
	 * @param WebRequest $req
	 * @param RevisionRecord $latestRevision
	 *
	 * @return Status
	 */
	private function getStatus( WebRequest $req, RevisionRecord $latestRevision ) {
		$revLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		if ( $req->getCheck( 'restore' ) ) { // nearly the same as undoafter without undo
			$olderRevision = $revLookup->getRevisionById( $req->getInt( 'restore' ) );

			if ( !$olderRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'restore' ) );
			}

			// ignore undo, even if set
			$newerRevision = $latestRevision;
		} elseif ( $req->getCheck( 'undo' ) ) {
			$newerRevision = $revLookup->getRevisionById( $req->getInt( 'undo' ) );

			if ( !$newerRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'undo' ) );
			}

			if ( $req->getCheck( 'undoafter' ) ) {
				$olderRevision = $revLookup->getRevisionById( $req->getInt( 'undoafter' ) );

				if ( !$olderRevision ) {
					return Status::newFatal( 'undo-norev', $req->getInt( 'undoafter' ) );
				}
			} else {
				$olderRevision = $revLookup->getPreviousRevision( $newerRevision );

				if ( !$olderRevision ) {
					return Status::newFatal( 'wikibase-undo-firstrev' );
				}
			}
		} elseif ( $req->getCheck( 'undoafter' ) ) {
			$olderRevision = $revLookup->getRevisionById( $req->getInt( 'undoafter' ) );

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

		if ( $newerRevision->getPageId() != $latestRevision->getPageId() ) {
			return Status::newFatal( 'wikibase-undo-badpage', $this->getTitle(), $newerRevision->getId() );
		}

		if ( $olderRevision->getPageId() != $latestRevision->getPageId() ) {
			return Status::newFatal( 'wikibase-undo-badpage', $this->getTitle(), $olderRevision->getId() );
		}

		if ( $olderRevision->getContent( SlotRecord::MAIN ) === null ) {
			return Status::newFatal( 'wikibase-undo-nocontent', $this->getTitle(), $olderRevision->getId() );
		}

		if ( $newerRevision->getContent( SlotRecord::MAIN ) === null ) {
			return Status::newFatal( 'wikibase-undo-nocontent', $this->getTitle(), $newerRevision->getId() );
		}

		if ( $latestRevision->getContent( SlotRecord::MAIN ) === null ) {
			return Status::newFatal( 'wikibase-undo-nocontent', $this->getTitle(), $latestRevision->getId() );
		}

		return Status::newGood( [ $olderRevision, $newerRevision, $latestRevision ] );
	}

	/**
	 * Output an error page showing the given status
	 *
	 * @param Status $status The status to report.
	 */
	protected function showUndoErrorPage( Status $status ) {
		$this->getOutput()->prepareErrorPage(
			$this->msg( 'wikibase-undo-revision-error' ),
			$this->msg( 'errorpagetitle' )
		);

		$this->getOutput()->addHTML( $status->getMessage()->parse() );

		$this->getOutput()->returnToMain();
	}

	/**
	 * @see FormlessAction::show
	 *
	 * Calls parent show() action to just display the entity, unless an undo action is requested.
	 */
	public function show() {
		$req = $this->getRequest();

		if ( $req->getCheck( 'undo' ) || $req->getCheck( 'undoafter' ) || $req->getCheck( 'restore' ) ) {
			$this->showUndoForm();
		} else {
			parent::show();
		}
	}

	private function showUndoForm() {
		$this->getOutput()->enableOOUI();
		$req = $this->getRequest();

		if ( $this->showPermissionError( 'read' ) || $this->showPermissionError( 'edit' ) ) {
			return;
		}

		$revisions = $this->loadRevisions();
		if ( !$revisions->isOK() ) {
			$this->showUndoErrorPage( $revisions );
			return;
		}

		/**
		 * @var RevisionRecord $olderRevision
		 * @var RevisionRecord $newerRevision
		 * @var RevisionRecord $latestRevision
		 */
		list( $olderRevision, $newerRevision, $latestRevision ) = $revisions->getValue();

		/**
		 * @var EntityContent $olderContent
		 * @var EntityContent $newerContent
		 * @var EntityContent $latestContent
		 */
		$olderContent = $olderRevision->getContent( SlotRecord::MAIN );
		$newerContent = $newerRevision->getContent( SlotRecord::MAIN );
		$latestContent = $latestRevision->getContent( SlotRecord::MAIN );

		if ( $newerContent->isRedirect() !== $latestContent->isRedirect() ) {
			$this->getOutput()->addWikiMsg( $latestContent->isRedirect()
				? 'wikibase-undo-redirect-latestredirect'
				: 'wikibase-undo-redirect-latestnoredirect' );
			return;
		}

		$restore = $req->getCheck( 'restore' );

		$this->getOutput()->setPageTitle(
			$this->msg(
				$restore ? 'wikibase-restore-title' : 'wikibase-undo-title',
				$this->getTitleText(),
				$olderRevision->getId(),
				$newerRevision->getId()
			)
		);

		// diff from newer to older
		$diff = $newerContent->getDiff( $olderContent );

		if ( $newerRevision->getId() == $latestRevision->getId() ) {
			// if the revision to undo is the latest revision, then there can be no conflicts
			$appDiff = $diff;
		} else {
			$patchedCurrent = $latestContent->getPatchedCopy( $diff );
			$appDiff = $latestContent->getDiff( $patchedCurrent );
		}

		if ( !$restore ) {
			$omitted = $diff->count() - $appDiff->count();

			if ( !$appDiff->isEmpty() ) {
				$this->getOutput()->addWikiMsg( $omitted > 0 ? 'wikibase-partial-undo' : 'undo-success' );
			}

			if ( $omitted > 0 ) {
				$this->getOutput()->addWikiMsg( 'wikibase-omitted-undo-ops', $omitted );
			}
		}

		if ( $appDiff->isEmpty() ) {
			$this->getOutput()->addWikiMsg( 'wikibase-empty-undo' );
			return;
		}

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

		$this->displayUndoDiff( $appDiff );

		if ( $restore ) {
			$this->showConfirmationForm();
		} else {
			$this->showConfirmationForm( $newerRevision->getId() );
		}
	}

	/**
	 * Used for overriding the page HTML title with the label, if available, or else the id.
	 * This is passed via parser output and output page to save overhead on view / edit actions.
	 *
	 * @return string
	 */
	private function getTitleText() {
		$meta = $this->getOutput()->getProperty( 'wikibase-meta-tags' );

		return $meta['title'] ?? $this->getTitle()->getPrefixedText();
	}

	/**
	 * Returns a cancel link back to viewing the entity's page
	 *
	 * @return string
	 */
	private function getCancelLink() {
		return ( new ButtonWidget( [
			'id' => 'mw-editform-cancel',
			'href' => $this->getContext()->getTitle()->getLocalURL(),
			'label' => $this->msg( 'cancel' )->text(),
			'framed' => false,
			'flags' => 'destructive',
		] ) )->toString();
	}

	/**
	 * Add style sheets and supporting JS for diff display.
	 */
	private function showDiffStyle() {
		$this->getOutput()->addModuleStyles( 'mediawiki.diff.styles' );
	}

	/**
	 * Generate standard summary input and label (wgSummary), compatible to EditPage.
	 *
	 * @param string $labelText The html to place inside the label
	 *
	 * @return string HTML
	 */
	private function getSummaryInput( $labelText ) {
		$inputAttrs = [
			'name' => 'wpSummary',
			'maxLength' => 200,
			'size' => 60,
			'spellcheck' => 'true',
			'accessKey' => $this->msg( 'accesskey-summary' )->plain(),
		] + Linker::tooltipAndAccesskeyAttribs( 'summary' );

		return ( new FieldLayout(
			new TextInputWidget( $inputAttrs ),
			[
				'label' => new HtmlSnippet( $labelText ),
				'align' => 'top',
				'id' => 'wpSummaryLabel',
				'classes' => [ 'mw-summary' ],
			]
		) )->toString();
	}

	private function displayUndoDiff( EntityContentDiff $diff ) {
		$tableClass = 'diff diff-contentalign-' . $this->getTitle()->getPageLanguage()->alignStart();

		// add Wikibase styles, the diff may include entity links with labels, including fallback indicators
		$this->getOutput()->addModuleStyles( [ 'wikibase.alltargets' ] );

		$this->getOutput()->addHTML( Html::openElement( 'table', [ 'class' => $tableClass ] ) );

		$this->getOutput()->addHTML( '<colgroup>'
			. '<col class="diff-marker"><col class="diff-content">'
			. '<col class="diff-marker"><col class="diff-content">'
			. '</colgroup>' );
		$this->getOutput()->addHTML( Html::openElement( 'tbody' ) );

		$old = $this->msg( 'currentrev' )->parse();
		$new = $this->msg( 'yourtext' )->parse(); //XXX: better message?

		$this->getOutput()->addHTML( Html::openElement( 'tr', [ 'style' => 'vertical-align: top;' ] ) );
		$this->getOutput()->addHTML(
			Html::rawElement( 'td', [ 'colspan' => '2' ],
				Html::rawElement( 'div', [ 'id' => 'mw-diff-otitle1' ], $old )
			)
		);
		$this->getOutput()->addHTML(
			Html::rawElement( 'td', [ 'colspan' => '2' ],
				Html::rawElement( 'div', [ 'id' => 'mw-diff-ntitle1' ], $new )
			)
		);
		$this->getOutput()->addHTML( Html::closeElement( 'tr' ) );

		$this->getOutput()->addHTML( $this->entityDiffVisualizer->visualizeEntityContentDiff( $diff ) );

		$this->getOutput()->addHTML( Html::closeElement( 'tbody' ) );
		$this->getOutput()->addHTML( Html::closeElement( 'table' ) );

		$this->showDiffStyle();
	}

	/**
	 * @return string HTML
	 */
	private function getEditButton() {
		global $wgEditSubmitButtonLabelPublish;
		$msgKey = $wgEditSubmitButtonLabelPublish ? 'publishchanges' : 'savearticle';
		return ( new ButtonInputWidget( [
				'name' => 'wpSave',
				'value' => $this->msg( $msgKey )->text(),
				'label' => $this->msg( $msgKey )->text(),
				'accessKey' => $this->msg( 'accesskey-save' )->plain(),
				'flags' => [ 'primary', 'progressive' ],
				'type' => 'submit',
				'title' => $this->msg( 'tooltip-save' )->text() . ' [' . $this->msg( 'accesskey-save' )->text() . ']',
			] ) )->toString();
	}

	/**
	 * Shows a form that can be used to confirm the requested undo/restore action.
	 *
	 * @param int $undidRevision
	 */
	private function showConfirmationForm( $undidRevision = 0 ) {
		$req = $this->getRequest();

		$args = [
			'action' => 'submit',
		];

		if ( $req->getInt( 'undo' ) ) {
			$args[ 'undo' ] = $req->getInt( 'undo' );
		}

		if ( $req->getInt( 'undoafter' ) ) {
			$args[ 'undoafter' ] = $req->getInt( 'undoafter' );
		}

		if ( $req->getInt( 'restore' ) ) {
			$args[ 'restore' ] = $req->getInt( 'restore' );
		}

		$actionUrl = $this->getTitle()->getLocalURL( $args );

		$this->getOutput()->addHTML( Html::openElement( 'div', [ 'style' => 'margin-top: 1em;' ] ) );

		$this->getOutput()->addHTML( Html::openElement( 'form', [
			'id' => 'undo',
			'name' => 'undo',
			'method' => 'post',
			'action' => $actionUrl,
			'enctype' => 'multipart/form-data' ] ) );

		$this->getOutput()->addHTML( "<div class='editOptions'>\n" );

		$labelText = $this->msg( 'wikibase-summary-generated' )->escaped();
		$this->getOutput()->addHTML( $this->getSummaryInput( $labelText ) );
		$this->getOutput()->addHTML( Html::rawElement( 'br' ) );
		$this->getOutput()->addHTML( "<div class='editButtons'>\n" );
		$this->getOutput()->addHTML( $this->getEditButton() . "\n" );
		$this->getOutput()->addHTML( $this->getCancelLink() );

		$this->getOutput()->addHTML( "</div><!-- editButtons -->\n</div><!-- editOptions -->\n" );

		$hidden = [
			'wpEditToken' => $this->getUser()->getEditToken(),
			'wpBaseRev' => $this->getTitle()->getLatestRevID(),
		];
		if ( !empty( $undidRevision ) ) {
			$hidden['wpUndidRevision'] = $undidRevision;
		}
		foreach ( $hidden as $name => $value ) {
			$this->getOutput()->addHTML( "\n" . Html::hidden( $name, $value ) . "\n" );
		}

		$this->getOutput()->addHTML( Html::closeElement( 'form' ) );
		$this->getOutput()->addHTML( Html::closeElement( 'div' ) );
	}

	/**
	 * @see Action::requiresUnblock
	 *
	 * @return bool Always true.
	 */
	public function requiresUnblock() {
		return true;
	}

	/**
	 * @see Action::requiresWrite
	 *
	 * @return bool Always true.
	 */
	public function requiresWrite() {
		return true;
	}

}
