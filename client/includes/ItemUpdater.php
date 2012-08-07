<?php

namespace Wikibase;
use Title;

/**
 * Handler updates to items caused by propagated changes.
 * Currently handling ItemChange, ItemCreation and ItemDeletion.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemUpdater {

	/**
	 * Handle the provided item change by doing
	 * the needed local updates.
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 */
	public function handleChange( Change $change ) {
		/**
		 * @var Item $item
		 */
		$item = $change->getItem();
		$siteLinks = $item->getSiteLinks();

		$globalId = 'enwiki'; // TODO

		if ( array_key_exists( $globalId, $siteLinks ) ) {
			$title = \Title::newFromText( $siteLinks[$globalId] );

			if ( !is_null( $title ) ) {
				list( , $subType ) = explode( '-', $change->getType() );

				$this->updateLocalItem( $subType, $item, $title );

				if ( $title->getArticleID() !== 0 ) {
					$title->invalidateCache();
				}
			}
		}
	}

	/**
	 * Updates the \Wikibase\LocalItem holding the \Wikibase\Item associated with the change.
	 *
	 * @since 0.1
	 *
	 * @param $changeType
	 * @param Item $item
	 * @param \Title $title
	 */
	protected function updateLocalItem( $changeType, Item $item, Title $title ) {
		$localItem = LocalItem::newFromItem( $item );

		$localItem->setField( 'page_title', $title->getFullText() );

		if ( $changeType === 'remove' ) {
			$localItem->remove();
		}
		else {
			$localItem->save();
		}
	}

}
