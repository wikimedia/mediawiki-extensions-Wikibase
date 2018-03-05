<?php

namespace Wikibase;

use Article;
use Html;
use MWException;
use OutputPage;
use ViewAction;
use Wikibase\Repo\WikibaseRepo;

/**
 * Handles the view action for Wikibase entities.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 */
class ViewEntityAction extends ViewAction {

	/**
	 * Handler for the BeforeDisplayNoArticleText called by Article.
	 * We implement this solely to replace the standard message that
	 * is shown when an entity does not exists.
	 *
	 * @param Article $article
	 * @return bool
	 * @throws MWException
	 */
	public static function onBeforeDisplayNoArticleText( Article $article ) {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$contentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		$ns = $article->getTitle()->getNamespace();
		$entityType = $namespaceLookup->getEntityType( $ns );
		$oldid = $article->getOldID();

		if ( $entityType !== null && !$oldid ) {
			$handler = $contentFactory->getContentHandlerForType( $entityType );
			$handler->showMissingEntity( $article->getTitle(), $article->getContext() );

			return false;
		}

		return true;
	}

	/**
	 * @see ViewAction::show
	 *
	 * Parent is doing $this->checkCanExecute( $this->getUser() )
	 */
	public function show() {
		$this->showEntityPage();
	}

	/**
	 * @return bool False for older revisions, or if this is for sure not a plain view (e.g. diff or
	 *  print view).
	 */
	private function isEditable() {
		return $this->page->isCurrent()
			&& !$this->isDiff()
			&& !$this->getOutput()->isPrintable();
	}

	/**
	 * @return bool
	 */
	private function isDiff() {
		return $this->getRequest()->getCheck( 'diff' );
	}

	private function showEntityPage() {
		$outputPage = $this->getOutput();

		// NOTE: page-wide property, independent of user permissions
		$outputPage->addJsConfigVars( 'wbIsEditView', $this->isEditable() );
		$this->page->view();

		$this->overridePageMetaTags( $outputPage );
	}

	/**
	 * This will be the label, if available, or else the entity id (e.g. 'Q42').
	 * This is passed via parser output and output page to save overhead on view actions.
	 *
	 * @param OutputPage $outputPage
	 */
	private function overridePageMetaTags( OutputPage $outputPage ) {
		$meta = $this->getOutput()->getProperty( 'wikibase-meta-tags' );

		if ( $this->isDiff() ) {
			if ( isset( $meta['title'] ) ) {
				$this->setDiffPageTitle( $outputPage, $meta['title'] );
			}

			// No description, social media tags, or any search engine optimization for diffs
			return;
		}

		if ( isset( $meta['title'] ) ) {
			$this->setHTMLTitle( $outputPage, $meta['title'] );
			$outputPage->addMeta( 'og:title', $meta['title'] );
		}

		if ( isset( $meta['description'] ) ) {
			$outputPage->addMeta( 'description', $meta['description'] );
			$outputPage->addMeta( 'og:description', $meta['description'] );

			if ( isset( $meta['title'] ) ) {
				$outputPage->addMeta( 'twitter:card', 'summary' );
			}
		}
	}

	/**
	 * @param OutputPage $outputPage
	 * @param string $titleText
	 */
	private function setDiffPageTitle( OutputPage $outputPage, $titleText ) {
		$variables = $outputPage->getJsConfigVars();

		if ( !isset( $variables['wbEntityId'] ) ) {
			wfLogWarning( "'wbEntityId' has not been found." );
			$id = '';
		} else {
			$id = ' ' . Html::element(
				'span',
				[ 'class' => 'wikibase-title-id' ],
				$this->msg( 'parentheses' )->plaintextParams( $variables['wbEntityId'] )
			);
		}

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
			)->plaintextParams( htmlspecialchars( $titleText ) . $id )
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
	 * @see Action::getDescription
	 *
	 * @return string Empty.
	 */
	protected function getDescription() {
		return '';
	}

	/**
	 * @see Action::requiresUnblock
	 *
	 * @return bool Always false.
	 */
	public function requiresUnblock() {
		return false;
	}

	/**
	 * @see Action::requiresWrite
	 *
	 * @return bool Always false.
	 */
	public function requiresWrite() {
		return false;
	}

}
