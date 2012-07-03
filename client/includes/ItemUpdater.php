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
					$this->updateLanglinksTable( $subType, $siteLinks, $title );
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

	/**
	 * Updates the langlinks table to include the links of the item.
	 *
	 * @since 0.1
	 *
	 * @param $changeType
	 * @param array $siteLinks
	 * @param \Title $title
	 */
	protected function updateLanglinksTable( $changeType, array $siteLinks, Title $title ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->begin( __METHOD__ );

		if ( $changeType === 'update' || $changeType === 'remove' ) {
			$dbw->delete(
				'langlinks',
				array(
					'll_from' => $title->getArticleID(),
					'll_local' => 0,
				),
				__METHOD__
			);
		}

		if ( $changeType === 'update' || $changeType === 'add' ) {
			$sites = Sites::singleton()->getAllSites();

			$localLinkIds = $this->getLocalLinkLocalIds( $dbw, $title->getArticleID() );

			// TODO: we need to hold into account the per-page and global langlink preferences
			// (once they exist) here to figure out which links we should store.

			foreach ( $siteLinks as $globalSiteId => $pageName ) {
				// TODO: nicify this stuff once we have the SiteLink objects.
				$localId = $sites->getSiteByGlobalId( $globalSiteId )->getConfig()->getLocalId();

				if ( !in_array( $localId, $localLinkIds ) ) {
					$dbw->insert(
						'langlinks',
						array(
							'll_local' => 0,
							'll_from' => $title->getArticleID(),
							'll_lang' => $localId,
							'll_title' => $pageName,
						),
						__METHOD__
					);
				}
			}
		}

		$dbw->commit( __METHOD__ );
	}

	/**
	 * Returns the local site identifiers of the local links.
	 *
	 * @since 0.1
	 *
	 * @param \DatabaseBase $dbw
	 * @param integer $articleId
	 *
	 * @return array
	 */
	protected function getLocalLinkLocalIds( \DatabaseBase $dbw, $articleId ) {
		$localLinks = $dbw->select(
			'langlinks',
			array(
				'll_lang',
			),
			array(
				'll_local' => 1,
				'll_from' => $articleId,
			),
			__METHOD__
		);

		$localLinkIds = array();

		foreach ( $localLinks as $localLink ) {
			$localLinkIds[] = $localLink->ll_lang;
		}

		return $localLinkIds;
	}

}
