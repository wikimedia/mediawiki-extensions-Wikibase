<?php

namespace Wikibase;

use Article;
use ContentHandler;
use LogEventsList;
use OutOfBoundsException;
use OutputPage;
use SpecialPage;
use ViewAction;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelLookup;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Store\PageEntityIdLookup;
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
	 * @todo inject the dependencies, with actions constructed with callback.
	 *
	 * @return LabelLookup
	 */
	public function getLabelLookup() {
		return new LanguageFallbackLabelLookup(
			new EntityRetrievingTermLookup( WikibaseRepo::getDefaultInstance()->getEntityLookup() ),
			$this->getLanguageFallbackChain()
		);
	}

	/**
	 * @todo inject or at least allow override
	 * @return PageEntityIdLookup
	 */
	public function getPageEntityIdLookup() {
		return WikibaseRepo::getDefaultInstance()->getPageEntityIdLookup();
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
	 * Parent is doing $this->checkCanExecute( $this->getUser() )
	 */
	public function show() {
		if ( !$this->getArticle()->getPage()->exists() ) {
			// @fixme could use ShowMissingArticle hook instead.
			// Article checks for missing / deleted revisions and either
			// shows appropriate error page or deleted revision, if permission allows.
			$this->displayMissingEntity();
		} else {
			$this->viewEntityPage();
		}
	}

	/**
	 * Returns true if this view action is performing a plain view (not a diff, etc)
	 * of the page's current revision.
	 *
	 * @return bool
	 */
	private function isEditable() {
		return !$this->isDiff() && $this->getArticle()->isCurrent();
	}

	/**
	 * @return bool
	 */
	private function isDiff() {
		return $this->getRequest()->getCheck( 'diff' );
	}

	/**
	 * Displays the entity page.
	 *
	 * @since 0.1
	 */
	private function viewEntityPage() {
		$outputPage = $this->getOutput();

		$editable = $this->isEditable();

		// NOTE: page-wide property, independent of user permissions
		$outputPage->addJsConfigVars( 'wbIsEditView', $editable );

		$user = $this->getContext()->getUser();
		$parserOptions = $this->getArticle()->getPage()->makeParserOptions( $user );

		$this->getArticle()->setParserOptions( $parserOptions );
		$this->getArticle()->view();

		$this->applyLabelToTitleText( $outputPage );
	}

	/**
	 * @param OutputPage $outputPage
	 */
	private function applyLabelToTitleText( OutputPage $outputPage ) {
		// Figure out which label to use for title.
		$labelText = $this->getLabelText();

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
	 * @return string
	 */
	protected function getLabelText() {
		wfProfileIn( __METHOD__ );

		$labelText = $this->getOutput()->getProperty( 'wikibase-entity-labeltext' );

		// fallback if not in parser cache
		if ( !$labelText ) {
			$title = $this->page->getTitle();
			$entityId = $this->getPageEntityIdLookup()->getPageEntityId( $title );

			try {
				$labelText = $this->getLabelLookup()->getLabel( $entityId );
			} catch ( OutOfBoundsException $ex ) {
				$labelText = $entityId->getSerialization();
			}
		}

		wfProfileOut( __METHOD__ );
		return $labelText;
	}

	/**
	 * Displays there is no entity for the current page.
	 *
	 * @since 0.1
	 */
	protected function displayMissingEntity() {
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
