<?php

namespace Wikibase;
use Language, Article;

/**
 * Handles the view action for Wikibase entities.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
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
	 * Returns the content of the page in the revision being viewed.
	 *
	 * @todo split out the get revision id stuff, add tests and see if
	 * any core code can be shared here
	 *
	 * @return EntityContent|null
	 */
	protected function getContent() {
        $title = $this->getTitle();
		$queryValues = $this->getRequest()->getQueryValues();

		if ( array_key_exists( 'diff', $queryValues ) ) {
			$oldId = $this->getArticle()->getOldID();
			$revision = $this->getDiffRevision( $oldId, $queryValues['diff'] );
		} else {
			$revisionId = $this->getArticle()->getOldID();
			$revision = \Revision::newFromTitle( $title, $revisionId );
		}

		if ( $revision !== null ) {
			return $revision->getContent();
		} else {
			return null;
		}
	}

	/**
	 * Get the revision specified in the diff parameter or prev/next revision of oldid
	 *
	 * @todo ViewEntityAction really isn't a great place for this
	 *
	 * @since 0.4
	 *
	 * @param int $oldId
	 * @param string|int $diffValue
	 *
	 * @return \Revision|null
	 */
	public function getDiffRevision( $oldId, $diffValue ) {
		if ( in_array( $diffValue, array( 'prev', 'next' ) ) ) {
			$revision = \Revision::newFromId( $oldId );

			if ( $revision !== null ) {
				if ( $diffValue === 'prev' ) {
					return $revision->getPrevious();
				} else {
					return $revision->getNext();
				}
			}
		} else {
			$revId = (int)$diffValue;
			$revision = \Revision::newFromId( $revId );
		}

		return $revision;
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
			$this->getArticle()->getRevisionFetched();

			$this->displayEntityContent( $content );

			$isEditableView = $this->isPlainView();

			$view = EntityView::newForEntityContent( $content );
			$view->registerJsConfigVars(
				$this->getOutput(),
				$content,
				$this->getLanguage()->getCode(),
				$isEditableView
			);
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
		$editable = ( $editable && $content->userCanEdit( null, false ) );

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
		// FIXME: Removed as a quickfix
		/*
		list( $labelCode, $labelText, $labelLang) =
			Utils::lookupUserMultilangText(
				$content->getEntity()->getLabels(),
				Utils::languageChain( $langCode ),
				array( $langCode, $this->getPageTitle(), $this->getContext()->getLanguage() )
			);
*/
		// FIXME: this replaces the stuff above
		$labelText = $content->getEntity()->getLabel( $langCode );

		if ( $labelText === false ) {
			$labelText = strtoupper( $content->getEntity()->getPrefixedId() );
		}

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
			$this->getOutput()->setHTMLTitle( $this->msg( 'pagetitle' )->params( $labelText ) );
		}
	}

	/**
	 * Displays there is no entity for the current page.
	 *
	 * @since 0.1
	 */
	protected function displayMissingEntity() {
		global $wgSend404Code;

		$title = $this->getArticle()->getTitle();
		$oldid = $this->getArticle()->getOldID();

		$out = $this->getOutput();

		$out->setPageTitle( $title->getPrefixedText() );

		// TODO: Factor the "show stuff for missing page" code out from Article::showMissingArticle,
		//       so it can be re-used here. The below code is copied & modified from there...

		wfRunHooks( 'ShowMissingArticle', array( $this ) );

		# Show delete and move logs
		\LogEventsList::showLogExtract( $out, array( 'delete', 'move' ), $title, '',
			array(  'lim' => 10,
			        'conds' => array( "log_action != 'revision'" ),
			        'showIfEmpty' => false,
			        'msgKey' => array( 'moveddeleted-notice' ) )
		);

		if ( $wgSend404Code ) {
			// If there's no backing content, send a 404 Not Found
			// for better machine handling of broken links.
			$this->getContext()->getRequest()->response()->header( "HTTP/1.1 404 Not Found" );
		}

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
				$entityHandler = \ContentHandler::getForTitle( $this->getTitle() );
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
					$createEntityPage = \SpecialPage::getTitleFor( $entityCreationPage );
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
