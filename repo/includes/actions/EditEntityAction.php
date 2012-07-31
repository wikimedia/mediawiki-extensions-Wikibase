<?php

namespace Wikibase;
use Content, Html;

/**
 * Handles the edit action for Wikibase entities.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EditEntityAction extends ViewEntityAction {

	/**
	 * @see Action::getName()
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName() {
		return 'edit';
	}

	/**
	 * @see FormlessAction::show
	 *
	 * @since 0.1
	 */
	public function show() {
		$req = $this->getRequest();

		if ( $req->getCheck( 'undo' ) ) {
			$latestRevId = $this->getTitle()->getLatestRevID();

			if ( $latestRevId !== 0 ) {
				$latestRevision = \Revision::newFromId( $latestRevId );

				$olderRevision = \Revision::newFromId( $req->getInt( 'undo' ) );
				$newerRevision = \Revision::newFromId( $req->getInt( 'undoafter' ) );

				if ( !is_null( $latestRevision ) && !is_null( $olderRevision ) && !is_null( $newerRevision ) ) {
					/**
					 * @var EntityContent $latestContent
					 * @var EntityContent $olderContent
					 * @var EntityContent $newerContent
					 */
					$olderContent = $olderRevision->getContent();
					$newerContent = $newerRevision->getContent();
					$latestContent = $latestRevision->getContent();


					// TODO: set title and stuffs
					// TODO: add summary and submit things
                    $langCode = $this->getContext()->getLanguage()->getCode();
                    list( $labelCode, $labelText, $labelLang) =
                        Utils::lookupUserMultilangText(
                            $latestContent->getEntity()->getLabels(),
                            Utils::languageChain( $langCode ),
                            array( $langCode, $this->getPageTitle(), $this->getContext()->getLanguage() )
                        );
                    $this->getOutput()->setContext( $this->getContext() );
                    $this->getOutput()->setPageTitle(
                        $this->msg(
                            'difference-title',
                            $labelText
                        )
                    );
                    $diff = $olderContent->getEntity()->getDiff( $newerContent->getEntity() );

                    $diff = $diff->getApplicableDiff( $latestContent->getEntity()->toArray() );

					$this->displayUndoDiff( $diff );
				}
			}
		}
		else {
			parent::show();
		}
	}

    /**
     * Add style sheets and supporting JS for diff display.
     *
     * @since 0.1
     *
     */
    function showDiffStyle() {
        $this->getOutput()->addModuleStyles( 'mediawiki.action.history.diff' );
    }

	/**
	 * Displays the undo diff.
	 *
	 * @since 0.1
	 *
	 * @param EntityDiff $diff
	 */
	protected function displayUndoDiff( EntityDiff $diff ) {
		$diffView = $diff->getView();
        $diffView->setContext( $this->getContext() );
		$this->getOutput()->addHTML( Html::rawElement( 'table', array(),  '<colgroup><col class="diff-marker"> <col class="diff-content"><col class="diff-marker"> <col class="diff-content"></colgroup><tbody>' . $diffView->getHtml() . '</tbody>') );
        $this->showDiffStyle();
	}

}
