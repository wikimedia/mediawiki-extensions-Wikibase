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
	 * @see Action::getName()
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName() {
		return 'view';
	}

	public function show() {
		global $wgUser, $wgLang, $wgContLang;

		// Some fishy pseudo-casting.
		$article = $this->page; /* @var \Article $article */
		$itemContent = $article->getPage()->getContent(); /* @var ItemContent $itemContent */

		$out = $this->getContext()->getOutput();

		if ( $itemContent === null ) {
			$this->showMissingItem( $article->getTitle(), $article->getOldID() );
			return;
		}

		// View it!
		$article->view();

		// The view can do weird tricks due to revision ids, better check what we are displaying now.
		if ( $article->getPage()->getContentModel() !== $itemContent->getModel() ) { // hacky...
			// Article decided to show something completly else.
			return;
		}
		if ( $article->getPage()->getContent()->getItem()->getId() !== $itemContent->getItem()->getId() ) { // hacky...
			// Article are not on the same item anymore.
			return;
		}

		// Ok, we are viewing an item and it seems to be the same. Create the title and
		// do all the silly JS stuff etc.

		// Figure out which label to use for title.
		$langCode = $this->getContext()->getLanguage()->getCode();
		list( $labelCode, $labelText, $labelLang) =
			\Wikibase\Utils::lookupUserMultilangText(
				$itemContent->getItem()->getLabels(),
				\Wikibase\Utils::languageChain( $langCode ),
				array( $langCode, $this->getPageTitle(), $this->getContext()->getLanguage() )
			);

		// Create and set the title.
		if ( $this->getContext()->getRequest()->getCheck( 'diff' ) ) {
			$out->setPageTitle(
				$this->msg(
					'difference-title',
					// This should be something like the following, 
					// $labelLang->getDirMark() . $labelText . $wgLang->getDirMark()
					// or should set the attribute of the h1 to correct direction.
					// Still note that the direction is "auto" so guessing should
					// give the right direction in most cases.
					$labelText
				)
			);
		} else {
			//XXX: are we really sure?!
			$this->getOutput()->setPageTitle(
				$labelText
			);
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