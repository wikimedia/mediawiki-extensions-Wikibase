<?php

namespace Wikibase;

use Article;
use ContentHandler;
use LogEventsList;
use OutputPage;
use SpecialPage;
use ViewAction;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;

/**
 * Handles the view action for Wikibase entities.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 */
abstract class ViewEntityAction extends ViewAction {

	/**
	 * @var LanguageFallbackChain
	 */
	protected $languageFallbackChain;

	/**
	 * @var EntityPermissionChecker
	 */
	protected $permissionChecker;

	/**
	 * Get the language fallback chain.
	 * Uses the default WikibaseRepo instance to get the service if it was not previously set.
	 *
	 * @since 0.4
	 *
	 * @return LanguageFallbackChain
	 */
	public function getLanguageFallbackChain() {
		if ( $this->languageFallbackChain === null ) {
			$this->languageFallbackChain = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory()
				->newFromContext( $this->getContext() );
		}

		return $this->languageFallbackChain;
	}

	/**
	 * Set language fallback chain.
	 *
	 * @since 0.4
	 *
	 * @param LanguageFallbackChain $chain
	 */
	public function setLanguageFallbackChain( LanguageFallbackChain $chain ) {
		$this->languageFallbackChain = $chain;
	}

	/**
	 * @see Action::getName()
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName() {
		return 'view';
	}

	/**
	 * Returns the current article.
	 *
	 * @since 0.1
	 *
	 * @return Article
	 */
	protected function getArticle() {
		return $this->page;
	}

	/**
	 * @see FormlessAction::show()
	 *
	 * @since 0.1
	 *
	 * TODO: permissing checks?
	 * Parent is doing $this->checkCanExecute( $this->getUser() )
	 */
	public function show() {
		if ( !$this->getArticle()->getPage()->exists() ) {
			$this->displayMissingEntity();
		} else {
			$contentRetriever = new ContentRetriever();
			$content = $contentRetriever->getContentForRequest(
				$this->getRequest(),
				$this->getArticle()
			);

			$this->displayEntityContent( $content );
		}
	}

	/**
	 * Returns true if this view action is performing a plain view (not a diff, etc)
	 * of the page's current revision.
	 */
	private function isEditable() {
		if ( !$this->getArticle()->isCurrent() || $this->isDiff() ) {
			return false;
		}

		return true;
	}

	/**
	 * @return boolean
	 */
	private function isDiff() {
		return $this->getRequest()->getCheck( 'diff' );
	}

	/**
	 * Displays the entity content.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $content
	 */
	protected function displayEntityContent( EntityContent $content ) {
		$outputPage = $this->getOutput();
		$editable = $this->isEditable();

		// NOTE: page-wide property, independent of user permissions
		$outputPage->addJsConfigVars( 'wbIsEditView', $editable );

		if ( $editable && !$content->isRedirect() ) {
			$title = $this->getArticle()->getTitle();
			$editable = $title->quickUserCan( 'edit', $user );
		}

		$parserOptions = $this->getArticle()->getPage()->makeParserOptions( $this->getContext()->getUser() );

		if ( !$editable ) {
			// disable editing features ("sections" is a misnomer, it applies to the wikitext equivalent)
			$parserOptions->setEditSection( $editable );
		}

		$this->getArticle()->setParserOptions( $parserOptions );
		$this->getArticle()->view();

		$this->applyLabelToTitleText( $outputPage, $content );
	}

	/**
	 * @param OutputPage $outputPage
	 */
	private function applyLabelToTitleText( OutputPage $outputPage, EntityContent $content ) {
		// Figure out which label to use for title.
		$labelText = $this->getLabelText( $content );

		if ( $this->isDiff() ) {
			$this->setPageTitle( $outputPage, $labelText );
		} else {
			$this->setHTMLTitle( $outputPage, $labelText );
		}
	}

	/**
	 * @param OutputPage $outputPage
	 * @param string $labelText
	 */
	private function setPageTitle( OutputPage $outputPage, $labelText ) {
		// Escaping HTML characters in order to retain original label that may contain HTML
		// characters. This prevents having characters evaluated or stripped via
		// OutputPage::setPageTitle:
		$outputPage->setPageTitle(
			$this->msg(
				'difference-title'
				// This should be something like the following,
				// $labelLang->getDirMark() . $labelText . $wgLang->getDirMark()
				// or should set the attribute of the h1 to correct direction.
				// Still note that the direction is "auto" so guessing should
				// give the right direction in most cases.
			)->rawParams( htmlspecialchars( $labelText ) )
		);
	}

	/**
	 * @param OutputPage $outputPage
	 * @param string $labelText
	 */
	private function setHTMLTitle( OutputPage $outputPage, $labelText ) {
		// Prevent replacing {{...}} by using rawParams() instead of params():
		$outputPage->setHTMLTitle( $this->msg( 'pagetitle' )->rawParams( $labelText ) );
	}

	/**
	 * @param EntityContent $content
	 *
	 * @return string
	 */
	private function getLabelText( EntityContent $content ) {
		// Figure out which label to use for title.
		$languageFallbackChain = $this->getLanguageFallbackChain();
		$labelData = null;

		if ( !$content->isRedirect() ) {
			$labels = $content->getEntity()->getLabels();
			$labelData = $languageFallbackChain->extractPreferredValueOrAny( $labels );
		}

		if ( $labelData ) {
			return $labelData['value'];
		} else {
			return $content->getEntityId()->getSerialization();
		}
	}

	/**
	 * Displays there is no entity for the current page.
	 *
	 * @since 0.1
	 */
	protected function displayMissingEntity() {
		global $wgSend404Code;

		$title = $this->getArticle()->getTitle();
		$oldid = $this->getArticle()->getOldID();

		$out = $this->getOutput();

		$out->setPageTitle( $title->getPrefixedText() );

		// TODO: Factor the "show stuff for missing page" code out from Article::showMissingArticle,
		//       so it can be re-used here. The below code is copied & modified from there...

		wfRunHooks( 'ShowMissingArticle', array( $this->getArticle() ) );

		# Show delete and move logs
		LogEventsList::showLogExtract( $out, array( 'delete', 'move' ), $title, '',
			array(  'lim' => 10,
			        'conds' => array( "log_action != 'revision'" ),
			        'showIfEmpty' => false,
			        'msgKey' => array( 'moveddeleted-notice' ) )
		);

		if ( $wgSend404Code ) {
			// If there's no backing content, send a 404 Not Found
			// for better machine handling of broken links.
			$this->getRequest()->response()->header( "HTTP/1.1 404 Not Found" );
		}

		$hookResult = wfRunHooks( 'BeforeDisplayNoArticleText', array( $this ) );

		// XXX: ...end of stuff stolen from Article::showMissingArticle

		if ( $hookResult ) {
			// Show error message
			if ( $oldid ) {
				$text = wfMessage( 'missing-article',
					$this->getTitle()->getPrefixedText(),
					wfMessage( 'missingarticle-rev', $oldid )->plain() )->plain();
			} else {
				/** @var $entityHandler EntityHandler */
				$entityHandler = ContentHandler::getForTitle( $this->getTitle() );
				$entityCreationPage = $entityHandler->getSpecialPageForCreation();

				$text = wfMessage( 'wikibase-noentity' )->plain();

				if( $entityCreationPage !== null
					&& $this->getTitle()->quickUserCan( 'create', $this->getContext()->getUser() )
					&& $this->getTitle()->quickUserCan( 'edit', $this->getContext()->getUser() )
				) {
					/*
					 * add text with link to special page for creating an entity of that type if possible and
					 * if user has the rights for it
					 */
					$createEntityPage = SpecialPage::getTitleFor( $entityCreationPage );
					$text .= ' ' . wfMessage(
						'wikibase-noentity-createone',
						$createEntityPage->getPrefixedText() // TODO: might be nicer to use an 'action=create' instead
					)->plain();
				}
			}

			$text = "<div class='noarticletext'>\n$text\n</div>";

			$out->addWikiText( $text );
		}
	}

	/**
	 * @see Action::getDescription()
	 */
	protected function getDescription() {
		return '';
	}

	/**
	 * @see Action::requiresUnblock()
	 */
	public function requiresUnblock() {
		return false;
	}

	/**
	 * @see Action::requiresWrite()
	 */
	public function requiresWrite() {
		return false;
	}

}
