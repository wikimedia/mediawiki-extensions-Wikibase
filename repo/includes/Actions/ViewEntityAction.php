<?php

namespace Wikibase;

use Article;
use OutputPage;
use ViewAction;
use Wikibase\Repo\WikibaseRepo;
use Xml;

/**
 * Handles the view action for Wikibase entities.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 */
abstract class ViewEntityAction extends ViewAction {

	/**
	 * Handler for the BeforeDisplayNoArticleText called by Article.
	 * We implement this solely to replace the standard message that
	 * is shown when an entity does not exists.
	 *
	 * @param Article $article
	 * @return bool
	 * @throws \MWException
	 */
	public static function onBeforeDisplayNoArticleText( Article $article ) {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$contentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		$ns = $article->getTitle()->getNamespace();
		$oldid = $article->getOldID();
		if ( $namespaceLookup->isEntityNamespace( $ns ) && !$oldid ) {
			$typeMap = array_flip( $namespaceLookup->getEntityNamespaces() );
			$entityType = $typeMap[ $ns ]; // isEntityNamespace() guarantees that this is set.

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
	 * Displays the entity page.
	 */
	private function showEntityPage() {
		$outputPage = $this->getOutput();
		$editable = $this->isEditable();

		// NOTE: page-wide property, independent of user permissions
		$outputPage->addJsConfigVars( 'wbIsEditView', $editable );
		$this->page->view();

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
			return;
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
