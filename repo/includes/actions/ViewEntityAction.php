<?php

namespace Wikibase;

use ContentHandler;
use LogEventsList;
use OutputPage;
use SpecialPage;
use ViewAction;
use Wikibase\Repo\Content\EntityHandler;
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
	private $languageFallbackChain;

	/**
	 * Get the language fallback chain.
	 * Uses the default WikibaseRepo instance to get the service if it was not previously set.
	 *
	 * @since 0.4
	 *
	 * @return LanguageFallbackChain
	 */
	protected function getLanguageFallbackChain() {
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
	protected function setLanguageFallbackChain( LanguageFallbackChain $chain ) {
		$this->languageFallbackChain = $chain;
	}

	/**
	 * @see ViewAction::show
	 *
	 * TODO: permissing checks?
	 * Parent is doing $this->checkCanExecute( $this->getUser() )
	 */
	public function show() {
		if ( !$this->page->getPage()->exists() ) {
			$this->displayMissingEntity();
			return;
		}

		$contentRetriever = new ContentRetriever();
		$content = $contentRetriever->getContentForRequest(
			$this->getRequest(),
			$this->page
		);

		if ( $content !== null && !( $content instanceof EntityContent ) ) {
			$this->getOutput()->showErrorPage(
				'wikibase-entity-not-viewable-title',
				'wikibase-entity-not-viewable',
				$content->getModel()
			);
			return;
		}

		$this->viewEntity();

		if ( $content instanceof EntityContent ) {
			$this->applyLabelToTitleText( $this->getOutput(), $content );
		}
	}

	/**
	 * Returns true if this view action is performing a plain view (not a diff, etc)
	 * of the page's current revision.
	 *
	 * @return bool
	 */
	private function isEditable() {
		return !$this->isDiff() && $this->page->isCurrent();
	}

	/**
	 * @return bool
	 */
	private function isDiff() {
		return $this->getRequest()->getCheck( 'diff' );
	}

	/**
	 * Displays the entity. Article::view takes care of all permission related things
	 * and showing errors if eg. a revision doesn't exist.
	 */
	private function viewEntity() {
		$outputPage = $this->getOutput();
		$editable = $this->isEditable();

		// NOTE: page-wide property, independent of user permissions
		$outputPage->addJsConfigVars( 'wbIsEditView', $editable );

		$user = $this->getContext()->getUser();
		$parserOptions = $this->page->getPage()->makeParserOptions( $user );

		$this->page->setParserOptions( $parserOptions );
		$this->page->view();
	}

	/**
	 * @param OutputPage $outputPage
	 * @param EntityContent $content
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
	 */
	private function displayMissingEntity() {
		$title = $this->getTitle();
		$oldid = $this->page->getOldID();

		$out = $this->getOutput();

		$out->setPageTitle( $title->getPrefixedText() );

		// TODO: Factor the "show stuff for missing page" code out from Article::showMissingArticle,
		//       so it can be re-used here. The below code is copied & modified from there...

		wfRunHooks( 'ShowMissingArticle', array( $this->page ) );

		# Show delete and move logs
		LogEventsList::showLogExtract( $out, array( 'delete', 'move' ), $title, '',
			array(  'lim' => 10,
			        'conds' => array( "log_action != 'revision'" ),
			        'showIfEmpty' => false,
			        'msgKey' => array( 'moveddeleted-notice' ) )
		);

		$this->send404Code();

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

	private function send404Code() {
		global $wgSend404Code;

		if ( $wgSend404Code ) {
			// If there's no backing content, send a 404 Not Found
			// for better machine handling of broken links.
			$this->getRequest()->response()->header( 'HTTP/1.1 404 Not Found' );
		}
	}

	/**
	 * @see Action::getDescription
	 *
	 * @return string
	 */
	protected function getDescription() {
		return '';
	}

	/**
	 * @see Action::requiresUnblock
	 *
	 * @return bool
	 */
	public function requiresUnblock() {
		return false;
	}

	/**
	 * @see Action::requiresWrite
	 *
	 * @return bool
	 */
	public function requiresWrite() {
		return false;
	}

}
