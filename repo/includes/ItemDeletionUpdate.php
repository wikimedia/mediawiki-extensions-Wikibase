<?php

namespace Wikibase;

/**
 * Deletion update to handle deletion of Wikibase items.
 *
 * @since 0.1
 *
 * @file
 *
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDeletionUpdate extends \DataUpdate {

	/**
	 * @var ItemContent
	 */
	protected $itemContent;

	/**
	 * @param ItemContent $itemContent
	 */
	public function __construct( ItemContent $itemContent ) {
		$this->itemContent = $itemContent;
	}

	/**
	 * Returns the ItemContent that's being deleted.
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public function getItemContent() {
		return $this->itemContent;
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 */
	public function doUpdate() {
		$dbw = wfGetDB( DB_MASTER );

		$id = $this->itemContent->getItem()->getId();

		$dbw->begin( __METHOD__ );

		$dbw->delete(
			'wb_items',
			array( 'item_id' => $id ),
			__METHOD__
		);

		$dbw->delete(
			'wb_items_per_site',
			array( 'ips_item_id' => $id ),
			__METHOD__
		);

		$dbw->delete(
			'wb_texts_per_lang',
			array( 'tpl_item_id' => $id ),
			__METHOD__
		);

		$dbw->delete(
			'wb_aliases',
			array( 'alias_item_id' => $id ),
			__METHOD__
		);

		$dbw->commit( __METHOD__ );

		/**
		 * Gets called after the deletion of an item has been comitted,
		 * allowing for extensions to do additional cleanup.
		 *
		 * @since 0.1
		 *
		 * @param ItemStructuredSave $this
		 */
		wfRunHooks( 'WikibaseItemDeletionUpdate', array( $this ) );

		// TODO: we need to handle failures in this thing.
		// If the update breaks for some reason, and stuff remains for a deleted item, how do we get rid of it?
		// Sitelinks will cause problems since they will needlessly prohibit other items from being linked to their targets.
	}

}