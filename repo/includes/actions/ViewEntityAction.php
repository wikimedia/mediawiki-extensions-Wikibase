<?php

namespace Wikibase;

use Article;
use ContentHandler;
use LogEventsList;
use OutputPage;
use SpecialPage;
use ViewAction;
use Wikibase\Repo\Content\EntityHandler;

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
			$this->showEntityPage();
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
	private function showEntityPage() {
		$outputPage = $this->getOutput();

		$editable = $this->isEditable();

		// NOTE: page-wide property, independent of user permissions
		$outputPage->addJsConfigVars( 'wbIsEditView', $editable );

		$user = $this->getContext()->getUser();
		$parserOptions = $this->getArticle()->getPage()->makeParserOptions( $user );

		$this->getArticle()->setParserOptions( $parserOptions );
		$this->getArticle()->view();

		$this->overrideTitleText( $outputPage );
	}

	/**
	 * This will be the label, if available, or else the entity id (e.g. 'Q42').
	 * This is passed via parser output and output page to save overhead on view actions.
	 *
	 * @param OutputPage $outputPage
	 */
	private function overrideTitleText( OutputPage $outputPage ) {
		$titleText = $this->getOutput()->getProperty( 'wikibase-titletext' );

		if ( $titleText === null ) {
			$titleText = $this->getTitle()->getPrefixedText();
		}

		if ( $this->isDiff() ) {
			$this->setPageTitle( $outputPage, $titleText );
		} else {
			$this->setHTMLTitle( $outputPage, $titleText );
		}
	}

	/**
	 * @param OutputPage $outputPage
	 * @param string $titleText
	 */
	private function setPageTitle( OutputPage $outputPage, $titleText ) {
		// Escaping HTML characters in order to retain original label that may contain HTML
		// characters. This prevents having characters evaluated or stripped via
		// OutputPage::setPageTitle:
		$outputPage->setPageTitle(
			$this->msg(
				'difference-title'
				// This should be something like the following,
				// $labelLang->getDirMark() . $titleText . $wgLang->getDirMark()
				// or should set the attribute of the h1 to correct direction.
				// Still note that the direction is "auto" so guessing should
				// give the right direction in most cases.
			)->rawParams( htmlspecialchars( $titleText ) )
		);
	}

	/**
	 * @param OutputPage $outputPage
	 * @param string $titleText
	 */
	private function setHTMLTitle( OutputPage $outputPage, $titleText ) {
		// Prevent replacing {{...}} by using rawParams() instead of params():
		$outputPage->setHTMLTitle( $this->msg( 'pagetitle' )->rawParams( $titleText ) );
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
