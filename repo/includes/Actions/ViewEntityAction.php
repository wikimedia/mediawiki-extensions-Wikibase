<?php

namespace Wikibase;

use ContentHandler;
use Hooks;
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
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 */
abstract class ViewEntityAction extends ViewAction {

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

		$user = $this->getUser();
		$parserOptions = $this->page->makeParserOptions( $user );

		$this->page->setParserOptions( $parserOptions );
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
