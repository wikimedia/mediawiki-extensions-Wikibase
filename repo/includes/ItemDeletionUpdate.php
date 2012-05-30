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
	 * @var Item
	 */
	protected $item;

	/**
	 * @param Item $item
	 */
	public function __construct( Item $item ) {
		$this->item = $item;
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 */
	public function doUpdate() {
		$dbw = wfGetDB( DB_MASTER );

		$id = $this->item->getId();

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

		// TODO: we need to handle failures in this thing.
		// If the update breaks for some reason, and stuff remains for a deleted item, how do we get rid of it?
		// Sitelinks will cause problems since they will needlessly prohibit other items from being linked to their targets.
	}

}