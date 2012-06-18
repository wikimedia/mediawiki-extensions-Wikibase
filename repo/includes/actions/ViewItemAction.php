<?php

namespace Wikibase;
use Language;

/**
 * Handles the view action for Wikibase items.
 *
 * TODO: utilized CachedAction once in core
 *
 * @since 0.1
 *
 * @file WikibaseViewItemAction.php
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ViewItemAction extends \FormlessAction {

	/**
	 * (non-PHPdoc)
	 * @see Action::getName()
	 */
	public function getName() {
		return 'view';
	}

	/**
	 * (non-PHPdoc)
	 * @see FormlessAction::onView()
	 */
	public function onView() {
		# If we got diff in the query, we want to see a diff page instead of the article.
		if ( $this->getContext()->getRequest()->getCheck( 'diff' ) ) {
			wfDebug( __METHOD__ . ": showing diff page\n" );
			$this->showDiffPage(  );
			wfProfileOut( __METHOD__ );

			return;
		}

		$content = $this->getContext()->getWikiPage()->getContent();

		if ( is_null( $content ) ) {
			// TODO: show ui for editing an empty item that does not have an ID yet.
		}
		else {
			// TODO: switch on type of content.
			$view = new ItemView( $this->getContext() );
			$view->render( $content );

			$this->getOutput()->setPageTitle( $content->getLabel( $this->getLanguage()->getCode() ) );
		}
		return '';
	}

	public function showDiffPage() {
		//XXX: would be nice if we could just inherit this from ViewAction.
		//XXX: maybe move logic from Article to ViewAction?

		//FIXME: don't allow editing? editing would revert the whole item!
		//FIXME: how to revert? how to undo???
		//FIXME: currrently, the diff title is editable!

		$title = $this->getContext()->getTitle();

		$article = new \Article( $title );
		$article->showDiffPage();
	}

		/**
	 * (non-PHPdoc)
	 * @see Action::getDescription()
	 */
	protected function getDescription() {
		return '';
	}

}