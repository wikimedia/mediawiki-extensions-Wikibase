<?php

namespace Wikibase;
use Content;

/**
 * Handles the edit action for Wikibase items.
 *
 * TODO: utilized CachedAction once in core
 *
 * @since 0.1
 *
 * @file WikibaseEditItemAction.php
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EditItemAction extends ViewItemAction {

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

					$diff = $olderContent->getEntity()->getDiff( $newerContent->getEntity() );

					$diff = $diff->getApplicableDiff( $latestContent->getEntity()->toArray() );

					// TODO: add relevant resources
					// TODO: set title and stuffs
					// TODO: add summary and submit things
					$this->displayUndoDiff( $diff );
				}
			}
		}
		else {
			parent::show();
		}
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

		$this->getOutput()->addHTML( $diffView->getHtml() );
	}

}
