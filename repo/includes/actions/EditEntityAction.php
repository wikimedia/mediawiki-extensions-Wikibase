<?php

namespace Wikibase;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Html;
use IContextSource;
use Linker;
use MWException;
use Page;
use Revision;
use Status;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use WebRequest;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\EscapingValueFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * Handles the edit action for Wikibase entities.
 * This shows the forms for the undo and restore operations if requested.
 * Otherwise it will just show the normal entity view.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 */
abstract class EditEntityAction extends ViewEntityAction {

	/**
	 * @var EntityDiffVisualizer
	 */
	private $entityDiffVisualizer;

	/**
	 * @param Page $page
	 * @param IContextSource|null $context
	 */
	public function __construct( Page $page, IContextSource $context = null ) {
		parent::__construct( $page, $context );

		$languageCode = $this->getContext()->getLanguage()->getCode();

		//TODO: proper injection
		$options = new FormatterOptions( array(
			//TODO: fallback chain
			ValueFormatter::OPT_LANG => $languageCode
		) );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$termLookup = new EntityRetrievingTermLookup( $wikibaseRepo->getEntityLookup() );
		$labelLookup = new LanguageLabelLookup( $termLookup, $languageCode );
		$labelFormatter = new EntityIdLabelFormatter( $options, $labelLookup );

		$propertyIdFormatter = new EscapingValueFormatter( $labelFormatter, 'htmlspecialchars' );

		$formatterFactory = $wikibaseRepo->getSnakFormatterFactory();
		$snakDetailsFormatter = $formatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML_DIFF, $options );
		$snakBreadCrumbFormatter = $formatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML, $options );

		$this->entityDiffVisualizer = new EntityDiffVisualizer(
			$this->getContext(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			new ClaimDifferenceVisualizer(
				$propertyIdFormatter,
				$snakDetailsFormatter,
				$snakBreadCrumbFormatter,
				$languageCode
			),
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getEntityRevisionLookup()
		);
	}

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
	 * @param string $action The action to check
	 *
	 * @return bool true if there were permission errors
	 */
	protected function showPermissionError( $action ) {
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
	 * @return Status a Status object containing an array with three revisions, ($olderRevision, $newerRevision, $latestRevision)
	 * @throws MWException if the page's latest revision can not be loaded
	 */
	protected function loadRevisions( ) {
		$latestRevId = $this->getTitle()->getLatestRevID();

		if ( $latestRevId === 0 ) {
			return Status::newFatal( 'missing-article', $this->getTitle()->getPrefixedText(), '' ); //XXX: better message
		}

		$latestRevision = Revision::newFromId( $latestRevId );

		if ( !$latestRevId ) {
			throw new MWException( "latest revision not found: $latestRevId" );
		}

		return $this->getStatus( $this->getRequest(), $latestRevision );
	}

	/**
	 * @param WebRequest $req
	 * @param Revision $latestRevision
	 *
	 * @return Status
	 */
	private function getStatus( WebRequest $req, Revision $latestRevision ){
		if ( $req->getCheck( 'restore' ) ) { // nearly the same as undoafter without undo
			$olderRevision = Revision::newFromId( $req->getInt( 'restore' ) );

			if ( !$olderRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'restore' ) );
			}

			// ignore undo, even if set
			$newerRevision = $latestRevision;
		} else if ( $req->getCheck( 'undo' ) ) {
			$newerRevision = Revision::newFromId( $req->getInt( 'undo' ) );

			if ( !$newerRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'undo' ) );
			}

			if ( $req->getCheck( 'undoafter' ) ) {
				$olderRevision = Revision::newFromId( $req->getInt( 'undoafter' ) );

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
			$olderRevision = Revision::newFromId( $req->getInt( 'undoafter' ) );

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

		return Status::newGood( array( $olderRevision, $newerRevision, $latestRevision ) );
	}

	/**
	 * Output an error page showing the given status
	 *
	 * @since 0.1
	 *
	 * @param string $title Message key for page title
	 * @param Status $status The status to report.
	 *
	 * @todo: would be handy to have this in OutputPage
	 */
	protected function showStatusErrorsPage( $title, Status $status ) {
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

	private function showUndoForm() {
		$req = $this->getRequest();

		if ( $this->showPermissionError( 'read' ) || $this->showPermissionError( 'edit' ) ) {
			return;
		}

		$revisions = $this->loadRevisions();
		if ( !$revisions->isOK() ) {
			$this->showStatusErrorsPage( 'wikibase-undo-revision-error', $revisions ); //TODO: define message
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
				$this->getOutput()->addHTML( Html::openElement( 'p' ) );
				$this->getOutput()->addWikiMsg( $omitted > 0 ? 'wikibase-partial-undo' : 'undo-success' );
				$this->getOutput()->addHTML( Html::closeElement( 'p' ) );
			}

			if ( $omitted > 0 ) {
				$this->getOutput()->addHTML( Html::openElement( 'p' ) );
				$this->getOutput()->addWikiMsg( 'wikibase-omitted-undo-ops', $omitted );
				$this->getOutput()->addHTML( Html::closeElement( 'p' ) );
			}
		}

		if ( $appDiff->isEmpty() ) {
			$this->getOutput()->addHTML( Html::openElement( 'p' ) );
			$this->getOutput()->addWikiMsg( 'wikibase-empty-undo' );
			$this->getOutput()->addHTML( Html::closeElement( 'p' ) );
			return;
		}

		$this->displayUndoDiff( $appDiff );

		if ( $restore ) {
			$this->showConfirmationForm(
				$this->makeRestoreSummary( $olderRevision )
			);
		} else {
			$this->showConfirmationForm(
				$this->makeUndoSummary( $newerRevision ),
				$newerRevision->getId()
			);
		}
	}

	/**
	 * Returns the label that should be shown to represent the given entity.
	 *
	 * @param EntityContent $content
	 *
	 * @return string
	 */
	private function getLabelText( EntityContent $content ) {
		$labelData = null;

		// TODO: use a message like <autoredircomment> to represent the redirect.
		if ( !$content->isRedirect() ) {
			$languageFallbackChain = $this->getLanguageFallbackChain();
			$labelData = $languageFallbackChain->extractPreferredValueOrAny( $content->getEntity()->getLabels() );
		}

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
	 * @param Revision $olderRevision
	 *
	 * @return string
	 */
	protected function makeRestoreSummary( Revision $olderRevision ) {
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
	 * @param Revision $newerRevision
	 *
	 * @return string
	 */
	protected function makeUndoSummary( Revision $newerRevision ) {
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
	 * @return string
	 */
	private function getCancelLink() {
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
	 */
	private function showDiffStyle() {
		$this->getOutput()->addModuleStyles( 'mediawiki.action.history.diff' );
	}

	/**
	 * Generate standard summary input and label (wgSummary), compatible to EditPage.
	 *
	 * @param $summary string The value of the summary input
	 * @param $labelText string The html to place inside the label
	 *
	 * @return array An array in the format array( $label, $input )
	 */
	private function getSummaryInput( $summary, $labelText ) {
		// Note: the maxlength is overriden in JS to 255 and to make it use UTF-8 bytes, not characters.
		$inputAttrs = array(
			'id' => 'wpSummary',
			'maxlength' => 200,
			'size' => 60,
			'spellcheck' => 'true',
		) + Linker::tooltipAndAccesskeyAttribs( 'summary' );

		$spanLabelAttrs = array(
			'class' => 'mw-summary',
			'id' => 'wpSummaryLabel',
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
	 * @param EntityContentDiff $diff
	 */
	private function displayUndoDiff( EntityContentDiff $diff ) {
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

		$this->getOutput()->addHTML( $this->entityDiffVisualizer->visualizeEntityContentDiff( $diff ) );

		$this->getOutput()->addHTML( Html::closeElement( 'tbody' ) );
		$this->getOutput()->addHTML( Html::closeElement( 'table' ) );

		$this->showDiffStyle();
	}

	/**
	 * @return string
	 */
	private function getEditButton() {
		return Html::element( 'input', array(
			'id'        => 'wpSave',
			'name'      => 'wpSave',
			'type'      => 'submit',
			'value'     => $this->msg( 'savearticle' )->text(),
			'accesskey' => $this->msg( 'accesskey-save' )->text(),
			'title'     => $this->msg( 'tooltip-save' )->text() . ' [' . $this->msg( 'accesskey-save' )->text() . ']',
		) );
	}

	/**
	 * Shows a form that can be used to confirm the requested undo/restore action.
	 *
	 * @param string $summary
	 * @param int $undidRevision
	 */
	private function showConfirmationForm( $summary, $undidRevision = 0 ) {
		$req = $this->getRequest();

		$args = array(
			'action' => 'submit',
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

		$this->getOutput()->addHTML( Html::openElement( 'div', array( 'style' => 'margin-top: 1em;' ) ) );

		$this->getOutput()->addHTML( Html::openElement( 'form', array(
			'id' => 'undo',
			'name' => 'undo',
			'method' => 'post',
			'action' => $actionUrl,
			'enctype' => 'multipart/form-data' ) ) );

		$this->getOutput()->addHTML( "<p class='editOptions'>\n" );

		$labelText = wfMessage( 'summary' )->text();
		list( $label, $field ) = $this->getSummaryInput( $summary, $labelText );
		$this->getOutput()->addHTML( $label . ' ' . $field );
		$this->getOutput()->addHTML( "<p class='editButtons'>\n" );
		$this->getOutput()->addHTML( $this->getEditButton() . "\n" );

		$cancel = $this->getCancelLink();
		if ( $cancel !== '' ) {
			$this->getOutput()->addHTML( wfMessage( 'pipe-separator' )->escaped() );
			$this->getOutput()->addHTML( $cancel );
		}

		$this->getOutput()->addHTML( "</p><!-- editButtons -->\n</p><!-- editOptions -->\n" );

		$hidden = array(
			'wpEditToken' => $this->getUser()->getEditToken(),
			'wpBaseRev' => $this->getTitle()->getLatestRevID(),
		);
		if ( !empty( $undidRevision ) ) {
			$hidden['wpUndidRevision'] = $undidRevision;
		}
		foreach ( $hidden as $name => $value ) {
			$this->getOutput()->addHTML( "\n" . Html::hidden( $name, $value ) . "\n" );
		}

		$this->getOutput()->addHTML( Html::closeElement( 'form' ) );
		$this->getOutput()->addHTML( Html::closeElement( 'div' ) );
	}

}
