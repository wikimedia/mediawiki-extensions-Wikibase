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
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 */
class ViewItemAction extends \ViewAction {

	/**
	 * (non-PHPdoc)
	 * @see Action::getName()
	 */
	public function getName() {
		return 'view';
	}

	public function show() {

		// some fishy pseudo-casting
		$article = $this->page; /* @var \Article $article */
		$itemContent = $article->getPage()->getContent(); /* @var ItemContent $itemContent */

		$out = $this->getContext()->getOutput();

		if ( $itemContent === null ) {
			$this->showMissingItem( $article->getTitle(), $article->getOldID() );
			return;
		}

		// view it!
		$article->view();

		if ( $article->getContentObject() !== $itemContent ) { // hacky...
			// Article decided to not show the item but something else. So skip all the Item stuff below.
			return;
		}

		// ok, we are viewing an item, do all the silly JS stuff etc.

		$langCode = $this->getContext()->getLanguage()->getCode();
		$label = $itemContent->getItem()->getLabel( $this->getLanguage()->getCode() );

		if ( $this->getContext()->getRequest()->getCheck( 'diff' ) ) {
			$out->setPageTitle( $this->msg( 'difference-title', $label ) );
		} else {
			//XXX: are we really sure?!
			$this->getOutput()->setPageTitle( $label );
		}

		ItemView::registerJsConfigVars( $out, $itemContent, $langCode );
	}

	/**
	 * Show the error text for a missing article. For articles in the MediaWiki
	 * namespace, show the default message text. To be called from Article::view().
	 */
	protected function showMissingItem( \Title $title, $oldid = 0 ) {
		global $wgSend404Code; //@todo: do send a 404?

		$outputPage = $this->getContext()->getOutput();

		$outputPage->setPageTitle( $title->getPrefixedText() );

		wfRunHooks( 'ShowMissingArticle', array( $this ) );

		$hookResult = wfRunHooks( 'BeforeDisplayNoArticleText', array( $this ) );

		if ( ! $hookResult ) {
			return;
		}

		# Show error message
		if ( $oldid ) {
			$text = wfMsgNoTrans( 'missing-article',
				$this->getTitle()->getPrefixedText(),
				wfMsgNoTrans( 'missingarticle-rev', $oldid ) );
		} elseif ( $this->getTitle()->quickUserCan( 'create', $this->getContext()->getUser() )
			&& $this->getTitle()->quickUserCan( 'edit', $this->getContext()->getUser() )
		) {
			$text = wfMsgNoTrans( 'wikibase-noitem' );
		} else {
			$text = wfMsgNoTrans( 'wikibase-noitem-nopermission' );
		}

		$text = "<div class='noarticletext'>\n$text\n</div>";

		$outputPage->addWikiText( $text );
	}

	/**
	 * (non-PHPdoc)
	 * @see Action::getDescription()
	 */
	protected function getDescription() {
		return '';
	}

	/**
	 * (non-PHPdoc)
	 * @see Action::requiresUnblock()
	 */
	public function requiresUnblock() {
		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Action::requiresWrite()
	 */
	public function requiresWrite() {
		return false;
	}
}