<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Actions;

use Article;
use MediaWiki\CommentStore\CommentStore;
use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Request\WebRequest;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Status\Status;
use OOUI\ButtonInputWidget;
use OOUI\ButtonWidget;
use OOUI\FieldLayout;
use OOUI\HtmlSnippet;
use OOUI\TextInputWidget;
use RuntimeException;
use Wikibase\Lib\Summary;
use Wikibase\Repo\AnonymousEditWarningBuilder;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\DispatchingEntityDiffVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;
use Wikibase\Repo\SummaryFormatter;

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
	 * {@link ObjectFactory} specification for this class,
	 * to be returned by {@link EntityHandler::getActionOverrides()} implementations.
	 */
	public const SPEC = [
		'class' => self::class,
		'services' => [
			'PermissionManager',
			'RevisionLookup',
			'WikibaseRepo.AnonymousEditWarningBuilder',
			'WikibaseRepo.EntityDiffVisualizerFactory',
			'WikibaseRepo.SummaryFormatter',
		],
	];

	protected PermissionManager $permissionManager;
	private RevisionLookup $revisionLookup;
	private EntityDiffVisualizer $entityDiffVisualizer;
	private AnonymousEditWarningBuilder $anonymousEditWarningBuilder;
	private SummaryFormatter $summaryFormatter;

	public function __construct(
		Article $article,
		IContextSource $context,
		PermissionManager $permissionManager,
		RevisionLookup $revisionLookup,
		AnonymousEditWarningBuilder $anonymousEditWarningBuilder,
		EntityDiffVisualizerFactory $entityDiffVisualizerFactory,
		SummaryFormatter $summaryFormatter
	) {
		parent::__construct( $article, $context );

		$this->permissionManager = $permissionManager;
		$this->revisionLookup = $revisionLookup;
		$this->entityDiffVisualizer = new DispatchingEntityDiffVisualizer(
			$entityDiffVisualizerFactory,
			$this->getContext()
		);
		$this->anonymousEditWarningBuilder = $anonymousEditWarningBuilder;
		$this->summaryFormatter = $summaryFormatter;
	}

	public function getName(): string {
		return 'edit';
	}

	/**
	 * Show an error page if the user is not allowed to perform the given action.
	 *
	 * @param string $action The action to check
	 *
	 * @return bool true if there were permission errors
	 */
	protected function showPermissionError( string $action ): bool {
		$rigor = $this->getRequest()->wasPosted() ?
			PermissionManager::RIGOR_SECURE : PermissionManager::RIGOR_FULL;

		$status = $this->permissionManager->getPermissionStatus(
			$action, $this->getUser(), $this->getTitle(), $rigor );

		if ( !$status->isGood() ) {
			$this->getOutput()->showPermissionStatus( $status, $action );

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
	 */
	protected function loadRevisions(): Status {
		$latestRevId = $this->getTitle()->getLatestRevID();

		if ( $latestRevId === 0 ) {
			// XXX: Better message
			return Status::newFatal( 'missing-article', $this->getTitle()->getPrefixedText(), '' );
		}

		$latestRevision = $this->revisionLookup->getRevisionById( $latestRevId );

		if ( !$latestRevId ) {
			throw new RuntimeException( "latest revision not found: $latestRevId" );
		}

		return $this->getStatus( $this->getRequest(), $latestRevision );
	}

	private function getStatus( WebRequest $req, RevisionRecord $latestRevision ): Status {
		if ( $req->getCheck( 'restore' ) ) { // nearly the same as undoafter without undo
			$olderRevision = $this->revisionLookup->getRevisionById( $req->getInt( 'restore' ) );

			if ( !$olderRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'restore' ) );
			}

			// ignore undo, even if set
			$newerRevision = $latestRevision;
		} elseif ( $req->getCheck( 'undo' ) ) {
			$newerRevision = $this->revisionLookup->getRevisionById( $req->getInt( 'undo' ) );

			if ( !$newerRevision ) {
				return Status::newFatal( 'undo-norev', $req->getInt( 'undo' ) );
			}

			if ( $req->getCheck( 'undoafter' ) ) {
				$olderRevision = $this->revisionLookup->getRevisionById( $req->getInt( 'undoafter' ) );

				if ( !$olderRevision ) {
					return Status::newFatal( 'undo-norev', $req->getInt( 'undoafter' ) );
				}
			} else {
				$olderRevision = $this->revisionLookup->getPreviousRevision( $newerRevision );

				if ( !$olderRevision ) {
					return Status::newFatal( 'wikibase-undo-firstrev' );
				}
			}
		} elseif ( $req->getCheck( 'undoafter' ) ) {
			$olderRevision = $this->revisionLookup->getRevisionById( $req->getInt( 'undoafter' ) );

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
	 */
	protected function showUndoErrorPage( Status $status ): void {
		$this->getOutput()->prepareErrorPage();
		$this->getOutput()->setPageTitleMsg(
			$this->msg( 'wikibase-undo-revision-error' )
		);
		$this->getOutput()->setHTMLTitle(
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
	public function show(): void {
		$req = $this->getRequest();

		if ( $req->getCheck( 'undo' ) || $req->getCheck( 'undoafter' ) || $req->getCheck( 'restore' ) ) {
			$this->showUndoForm();
		} else {
			parent::show();
		}
	}

	private function showUndoForm(): void {
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
		[ $olderRevision, $newerRevision, $latestRevision ] = $revisions->getValue();

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

		$this->getOutput()->setPageTitleMsg(
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
				$this->anonymousEditWarningBuilder->buildAnonymousEditWarningHTML( $this->getTitle()->getPrefixedText() ),
			) );
		}

		$this->displayUndoDiff( $appDiff );

		if ( $restore ) {
			$autoSummaryLength = mb_strlen( $this->makeSummary( 'restore', $olderRevision, 'x' ) ) - 1;
			$this->showConfirmationForm( $autoSummaryLength );
		} else {
			$autoSummaryLength = mb_strlen( $this->makeSummary( 'undo', $newerRevision, 'x' ) ) - 1;
			$this->showConfirmationForm( $autoSummaryLength, $newerRevision->getId() );
		}
	}

	protected function makeSummary( string $actionName, RevisionRecord $revision, string $userSummary ): string {
		$revUser = $revision->getUser();
		$revUserText = $revUser ? $revUser->getName() : '';

		$summary = new Summary();
		$summary->setAction( $actionName );
		$summary->addAutoCommentArgs( $revision->getId(), $revUserText );
		$summary->setUserSummary( $userSummary );

		return $this->summaryFormatter->formatSummary( $summary );
	}

	/**
	 * Used for overriding the page HTML title with the label, if available, or else the id.
	 * This is passed via parser output and output page to save overhead on view / edit actions.
	 */
	private function getTitleText(): string {
		$meta = $this->getOutput()->getProperty( 'wikibase-meta-tags' );

		return $meta['title'] ?? $this->getTitle()->getPrefixedText();
	}

	/**
	 * Returns a cancel link back to viewing the entity's page
	 */
	private function getCancelLink(): string {
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
	private function showDiffStyle(): void {
		$this->getOutput()->addModuleStyles( 'mediawiki.diff.styles' );
	}

	/**
	 * Generate standard summary input and label (wgSummary), compatible to \MediaWiki\EditPage\EditPage.
	 *
	 * @param string $labelText The html to place inside the label
	 * @param int $autoSummaryLength
	 *
	 * @return string HTML
	 */
	private function getSummaryInput( string $labelText, int $autoSummaryLength ): string {
		$inputAttrs = [
			'name' => 'wpSummary',
			'maxLength' => max( CommentStore::COMMENT_CHARACTER_LIMIT - $autoSummaryLength, 0 ),
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

	private function displayUndoDiff( EntityContentDiff $diff ): void {
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
	private function getEditButton(): string {
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
	 */
	private function showConfirmationForm( int $autoSummaryLength, int $undidRevision = 0 ): void {
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
		$this->getOutput()->addHTML( $this->getSummaryInput( $labelText, $autoSummaryLength ) );
		$this->getOutput()->addHTML( Html::rawElement( 'br' ) );
		$this->getOutput()->addHTML( "<div class='editButtons'>\n" );
		$this->getOutput()->addHTML( $this->getEditButton() . "\n" );
		$this->getOutput()->addHTML( $this->getCancelLink() );

		$this->getOutput()->addHTML( "</div><!-- editButtons -->\n</div><!-- editOptions -->\n" );

		$hidden = [
			'wpEditToken' => $this->getUser()->getEditToken(),
			'wpBaseRev' => $this->getTitle()->getLatestRevID(),
		];
		if ( $undidRevision !== 0 ) {
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
	public function requiresUnblock(): bool {
		return true;
	}

	/**
	 * @see Action::requiresWrite
	 *
	 * @return bool Always true.
	 */
	public function requiresWrite(): bool {
		return true;
	}

}
