<?php

namespace Wikibase;
use Language, Article;

/**
 * Handles the view action for Wikibase entities.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 */
abstract class ViewEntityAction extends \ViewAction {

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
	 * Returns the content of the page being viewed.
	 *
	 * @param \Article $article
	 *
	 * @return EntityContent|null
	 */
	protected function getContent() {
		return $this->getArticle()->getPage()->getContent();
	}

	/**
	 * Returns the current article.
	 *
	 * @since 0.1
	 *
	 * @return \Article
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
		$content = $this->getContent();

		if ( is_null( $content ) ) {
			$this->displayMissingEntity();
		}
		else {
			$this->displayEntityContent( $content );
		}
	}

	/**
	 * Returns true if this view action is performing a plain view (not a diff, etc)
	 * of the page's current revision.
	 */
	public function isPlainView() {
		if ( !$this->getArticle()->getPage()->exists() ) {
			// showing non-existing entity
			return false;
		}

		if ( $this->getArticle()->getOldID() > 0
			&&  ( $this->getArticle()->getOldID() !== $this->getArticle()->getPage()->getLatest() ) ) {
			// showing old content
			return false;
		}

		$content = $this->getContent();

		if ( !( $content instanceof EntityContent ) ) {
			//XXX: HACK against evil tricks in Article::getContentObject
			// showing strange content
			return false;
		}

		if ( $this->getContext()->getRequest()->getCheck( 'diff' ) ) {
			// showing a diff
			return false;
		}

		return true;
	}

	/**
	 * Displays the entity content.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $content
	 */
	protected function displayEntityContent( EntityContent $content ) {
		$out = $this->getOutput();

		// can edit?
		$editable = $this->isPlainView();
		$editable &= $this->getTitle()->quickUserCan( "read" );
		$editable &= $this->getTitle()->quickUserCan( "edit" );

		// View it!
		$parserOptions = $this->getArticle()->getPage()->makeParserOptions( $this->getContext()->getUser() );

		if ( !$editable ) {
			// disable editing features ("sections" is a misnomer, it applies to the wikitext equivalent)
			$parserOptions->setEditSection( $editable );
		}

		$this->getArticle()->setParserOptions( $parserOptions );
		$this->getArticle()->view();

		// Figure out which label to use for title.
		$langCode = $this->getContext()->getLanguage()->getCode();
		list( $labelCode, $labelText, $labelLang) =
			Utils::lookupUserMultilangText(
				$content->getEntity()->getLabels(),
				Utils::languageChain( $langCode ),
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
			$this->getOutput()->setPageTitle( $labelText );
		}
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

		// TODO: firing hooks from core here is NOT nice...
		wfRunHooks( 'ShowMissingArticle', array( $this ) );

		$hookResult = wfRunHooks( 'BeforeDisplayNoArticleText', array( $this ) );

		if ( $hookResult ) {
			// Show error message
			if ( $oldid ) {
				$text = wfMsgNoTrans( 'missing-article',
					$this->getTitle()->getPrefixedText(),
					wfMsgNoTrans( 'missingarticle-rev', $oldid ) );
			} elseif ( $this->getTitle()->quickUserCan( 'create', $this->getContext()->getUser() )
				&& $this->getTitle()->quickUserCan( 'edit', $this->getContext()->getUser() )
			) {
				$text = wfMsgNoTrans( 'wikibase-noitem' ); // TODO: item, not entity
			} else {
				$text = wfMsgNoTrans( 'wikibase-noitem-nopermission' ); // TODO: item, not entity
			}

			$text = "<div class='noarticletext'>\n$text\n</div>";

			$out->addWikiText( $text );
		}
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